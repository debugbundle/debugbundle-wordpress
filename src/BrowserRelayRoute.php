<?php

declare(strict_types=1);

namespace DebugBundleWp;

if (!defined('ABSPATH')) {
    exit;
}

use DebugBundle\Relay\BrowserRelayAcceptedBatch;
use DebugBundle\Relay\BrowserRelayHandler;

final class BrowserRelayRoute
{
    private readonly RateLimiter $rateLimiter;
    private readonly RelaySpool $spool;
    private readonly RelayForwarder $forwarder;

    public function __construct(
        private readonly Settings $settings,
        private readonly string $cronHook,
    ) {
        $this->rateLimiter = new RateLimiter();
        $this->spool = new RelaySpool();
        $this->forwarder = new RelayForwarder($settings);
    }

    public function register(): void
    {
        if (!function_exists('add_action')) {
            return;
        }

        \add_action('rest_api_init', [$this, 'registerRoute']);
    }

    public function registerRoute(): void
    {
        if (!function_exists('register_rest_route')) {
            return;
        }

        \register_rest_route('debugbundle/v1', '/browser', [
            'methods' => ['POST', 'OPTIONS'],
            'permission_callback' => '__return_true',
            'callback' => [$this, 'handleRequest'],
        ]);
    }

    public function ensureSpoolDirectory(): bool
    {
        return $this->spool->ensureDirectory();
    }

    public function handleRequest(mixed $request): mixed
    {
        if (!$this->settings->isFrontendCaptureEnabled()) {
            return $this->response(['accepted' => 0, 'rejected' => 0, 'errors' => ['frontend_capture_disabled']], 404);
        }

        $headers = method_exists($request, 'get_headers') ? (array) $request->get_headers() : Sanitization::requestHeadersFromServer(Sanitization::serverInputArray());
        $body = method_exists($request, 'get_body') ? (string) $request->get_body() : file_get_contents('php://input');
        $method = method_exists($request, 'get_method') ? (string) $request->get_method() : Sanitization::requestMethod('POST');
        $flattenedHeaders = $this->flattenHeaders($headers);

        if (strtoupper($method) === 'OPTIONS') {
            $origin = $this->sourceOrigin($flattenedHeaders);
            if ($origin === null || !$this->isAllowedOrigin($origin, $flattenedHeaders['host'] ?? null)) {
                return $this->response(null, 403);
            }
            return $this->response(null, 204, $this->corsHeaders($origin));
        }

        $ipAddress = Sanitization::ipAddress();
        if (!$this->rateLimiter->allow($ipAddress)) {
            if (function_exists('header')) {
                header('Retry-After: ' . $this->rateLimiter->retryAfter());
            }
            return $this->response(['accepted' => 0, 'rejected' => 0, 'errors' => ['rate_limited']], 429);
        }

        $acceptedEvents = [];
        $handler = new BrowserRelayHandler([
            'allowedOrigins' => $this->allowedOrigins(),
            'maxBodyBytes' => 262144,
            'rateLimitPerMinute' => 60,
            'service' => $this->settings->getBrowserService(),
            'environment' => $this->settings->getEnvironment(),
            'onAccept' => function (BrowserRelayAcceptedBatch $batch) use (&$acceptedEvents): void {
                foreach ($batch->events as $event) {
                    $acceptedEvents[] = $this->completeBrowserEventEnvelope($event);
                }
            },
        ]);

        $relayResponse = $handler->handle([
            'method' => $method,
            'headers' => $flattenedHeaders,
            'body' => is_string($body) ? $body : '',
            'ipAddress' => $ipAddress,
        ]);

        $responseHeaders = $relayResponse->headers ?? [];
        if ($responseHeaders === []) {
            $origin = $this->sourceOrigin($flattenedHeaders);
            if ($origin !== null && $this->isAllowedOrigin($origin, $flattenedHeaders['host'] ?? null)) {
                $responseHeaders = $this->corsHeaders($origin);
            }
        }

        if ($acceptedEvents !== []) {
            $spoolFile = $this->spool->write($acceptedEvents);
            if ($spoolFile !== null) {
                $result = $this->forwarder->forward($acceptedEvents);
                if ($result->success) {
                    $this->spool->delete($spoolFile);
                } elseif (!$result->drop) {
                    $this->scheduleFlush();
                } else {
                    $this->spool->delete($spoolFile);
                }
            }
        }

        return $this->response($relayResponse->body ?? null, $relayResponse->status, $responseHeaders);
    }

    public function flushSpool(): void
    {
        foreach (array_slice($this->spool->files(), 0, 25) as $filePath) {
            $contents = @file_get_contents($filePath);
            if (!is_string($contents) || $contents === '') {
                $this->spool->delete($filePath);
                continue;
            }

            $decoded = json_decode($contents, true);
            $events = is_array($decoded) && isset($decoded['events']) && is_array($decoded['events']) ? $decoded['events'] : null;
            if ($events === null) {
                $this->spool->delete($filePath);
                continue;
            }

            $result = $this->forwarder->forward($events);
            if ($result->success || $result->drop) {
                $this->spool->delete($filePath);
            }
        }
    }

    /** @return list<string> */
    private function allowedOrigins(): array
    {
        $origins = [];
        foreach (['home_url', 'site_url'] as $functionName) {
            if (!function_exists($functionName)) {
                continue;
            }

            $candidate = $functionName('/');
            if (!is_string($candidate) || $candidate === '') {
                continue;
            }

            $parts = \wp_parse_url($candidate);
            if (!is_array($parts) || !isset($parts['scheme'], $parts['host'])) {
                continue;
            }

            $origin = $parts['scheme'] . '://' . $parts['host'];
            if (isset($parts['port'])) {
                $origin .= ':' . $parts['port'];
            }
            $origins[] = $origin;
        }

        return array_values(array_unique($origins));
    }

    /** @param array<string, string> $headers */
    private function sourceOrigin(array $headers): ?string
    {
        $origin = trim($headers['origin'] ?? '');
        if ($origin !== '') {
            return $origin;
        }

        $referer = trim($headers['referer'] ?? '');
        if ($referer === '') {
            return null;
        }

        $parts = \wp_parse_url($referer);
        if (!is_array($parts) || !isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        $sourceOrigin = $parts['scheme'] . '://' . $parts['host'];
        if (isset($parts['port'])) {
            $sourceOrigin .= ':' . $parts['port'];
        }
        return $sourceOrigin;
    }

    private function isAllowedOrigin(string $origin, ?string $host = null): bool
    {
        $normalizedOrigin = $this->normalizeOrigin($origin);
        foreach ($this->allowedOrigins() as $allowedOrigin) {
            if ($this->normalizeOrigin($allowedOrigin) === $normalizedOrigin) {
                return true;
            }
        }

        if ($host !== null && trim($host) !== '') {
            $parts = \wp_parse_url($origin);
            $originHost = is_array($parts) && isset($parts['host']) ? strtolower((string) $parts['host']) : '';
            return $originHost !== '' && $originHost === strtolower(explode(':', trim($host))[0] ?? '');
        }

        return false;
    }

    private function normalizeOrigin(string $origin): string
    {
        return rtrim(strtolower(trim($origin)), '/');
    }

    /** @return array<string, string> */
    private function corsHeaders(string $origin): array
    {
        return [
            'Access-Control-Allow-Origin' => $origin,
            'Access-Control-Allow-Methods' => 'POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'content-type',
            'Access-Control-Max-Age' => '600',
            'Vary' => 'Origin',
        ];
    }

    /**
     * @param array<string, mixed> $event
     * @return array<string, mixed>
     */
    private function completeBrowserEventEnvelope(array $event): array
    {
        $service = isset($event['service']) && is_array($event['service']) ? $event['service'] : [];
        $service['runtime'] = $service['runtime'] ?? 'browser';
        $service['framework'] = $service['framework'] ?? null;
        $event['service'] = $service;

        if (isset($event['correlation']) && is_array($event['correlation'])) {
            $correlation = $event['correlation'];
            $event['correlation'] = [
                'request_id' => isset($correlation['request_id']) && is_string($correlation['request_id']) ? $correlation['request_id'] : null,
                'trace_id' => isset($correlation['trace_id']) && is_string($correlation['trace_id']) ? $correlation['trace_id'] : null,
                'session_id' => isset($correlation['session_id']) && is_string($correlation['session_id']) ? $correlation['session_id'] : null,
                'user_id_hash' => isset($correlation['user_id_hash']) && is_string($correlation['user_id_hash']) ? $correlation['user_id_hash'] : null,
            ];
        }

        return $event;
    }

    /** @param array<string, mixed> $headers
     *  @return array<string, string>
     */
    private function flattenHeaders(array $headers): array
    {
        $flattened = [];
        foreach ($headers as $name => $value) {
            if (!is_string($name)) {
                continue;
            }

            if (is_array($value)) {
                $value = reset($value);
            }

            if (is_scalar($value)) {
                $flattened[str_replace('_', '-', strtolower($name))] = (string) $value;
            }
        }

        return $flattened;
    }

    /** @param array<string, mixed>|null $body */
    /** @param array<string, string> $headers */
    private function response(?array $body, int $status, array $headers = []): mixed
    {
        if (class_exists('WP_REST_Response')) {
            return new \WP_REST_Response($body, $status, $headers);
        }

        return [
            'status' => $status,
            'body' => $body,
            'headers' => $headers,
        ];
    }

    private function scheduleFlush(): void
    {
        if (!function_exists('wp_next_scheduled') || !function_exists('wp_schedule_single_event')) {
            return;
        }

        if (!\wp_next_scheduled($this->cronHook)) {
            \wp_schedule_single_event(time() + 60, $this->cronHook);
        }
    }
}
