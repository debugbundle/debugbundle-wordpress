<?php

declare(strict_types=1);

namespace DebugBundleWp;

use DebugBundle\DebugBundleSdk;

final class AdminTestEvents
{
    private readonly Diagnostics $diagnostics;
    private readonly RelaySpool $spool;

    /** @var \Closure(): object */
    private readonly \Closure $relayRouteFactory;

    public function __construct(
        private readonly Settings $settings,
        ?Diagnostics $diagnostics = null,
        ?RelaySpool $spool = null,
        ?callable $relayRouteFactory = null,
    ) {
        $this->spool = $spool ?? new RelaySpool();
        $this->diagnostics = $diagnostics ?? new Diagnostics($this->spool);
        $this->relayRouteFactory = $relayRouteFactory !== null
            ? \Closure::fromCallable($relayRouteFactory)
            : function (): BrowserRelayRoute {
                return new BrowserRelayRoute($this->settings, 'debugbundle_admin_test_relay');
            };
    }

    public function sendBackend(): AdminTestResult
    {
        if (!$this->settings->isConfigured()) {
            return new AdminTestResult(false, 'Add a project token before sending a backend test event.');
        }

        if (!$this->settings->isBackendCaptureEnabled()) {
            return new AdminTestResult(false, 'Enable backend capture before sending a backend test event.');
        }

        $sdk = new DebugBundleSdk();

        try {
            $sdk->init([
                'projectToken' => $this->settings->getProjectToken(),
                'endpoint' => $this->settings->getEndpoint(),
                'service' => $this->settings->getService(),
                'environment' => $this->settings->getEnvironment(),
                'sampleRate' => 1.0,
                'batchSize' => 1,
                'logLevel' => 'warning',
                'redactFields' => ['wpnonce', '_wpnonce', 'woocommerce-login-nonce', 'woocommerce-register-nonce', 'woocommerce-reset-password-nonce'],
            ]);
            $sdk->captureException(new \RuntimeException('DebugBundle WordPress backend test event'), [
                'source' => 'wordpress_admin_test',
                'request' => [
                    'method' => 'POST',
                    'path' => '/wp-admin/options-general.php?page=debugbundle',
                    'headers' => [],
                    'query' => [],
                ],
                'response' => [
                    'status_code' => 200,
                ],
            ]);
            $sdk->flush();

            if ($sdk->getLastEventAt() !== null) {
                Diagnostics::recordBackendFlush($sdk->getLastEventAt());
                return new AdminTestResult(true, 'Backend test event sent to DebugBundle.');
            }

            Diagnostics::recordBackendError('Backend test event could not be delivered.');
            return new AdminTestResult(false, 'Backend test event could not be delivered. Check the endpoint and project token.');
        } catch (\Throwable $throwable) {
            Diagnostics::recordBackendError($throwable->getMessage());
            return new AdminTestResult(false, 'Backend test event failed: ' . $this->safeErrorMessage($throwable));
        } finally {
            $sdk->reset();
        }
    }

    public function sendFrontend(): AdminTestResult
    {
        if (!$this->settings->isConfigured()) {
            return new AdminTestResult(false, 'Add a project token before sending a frontend test event.');
        }

        if (!$this->settings->isFrontendCaptureEnabled()) {
            return new AdminTestResult(false, 'Enable frontend capture before sending a frontend test event.');
        }

        $payload = wp_json_encode([
            'batch' => [$this->browserTestEvent()],
        ]);

        if (!is_string($payload)) {
            return new AdminTestResult(false, 'Frontend test event could not be encoded.');
        }

        try {
            $beforeStatus = $this->diagnostics->status();
            $beforeSpoolCount = $this->spool->stats()['count'];
            $routeFactory = $this->relayRouteFactory;
            $route = $routeFactory();
            $response = $route->handleRequest(new class($this->sameOrigin(), $payload) {
                public function __construct(
                    private readonly string $origin,
                    private readonly string $body,
                ) {
                }

                /** @return array<string, string> */
                public function get_headers(): array
                {
                    return [
                        'origin' => $this->origin,
                        'content-type' => 'application/json',
                    ];
                }

                public function get_body(): string
                {
                    return $this->body;
                }

                public function get_method(): string
                {
                    return 'POST';
                }
            });

            $status = $this->responseStatus($response);
            $body = $this->responseBody($response);
            $accepted = $this->responseCount($body, 'accepted');
            $rejected = $this->responseCount($body, 'rejected');
            $errors = $this->responseErrors($body);

            if ($status < 200 || $status >= 300) {
                Diagnostics::recordRelayError('Frontend relay test event rejected with status ' . $status . '.');
                return new AdminTestResult(false, 'Frontend relay test event was rejected with status ' . $status . '.');
            }

            if ($accepted < 1) {
                return new AdminTestResult(false, 'Frontend relay test event was accepted by the route but no browser event passed validation.');
            }

            if ($rejected > 0 || $errors !== []) {
                return new AdminTestResult(false, 'Frontend relay test event was only partially accepted: ' . $this->formatErrors($errors));
            }

            $afterSpoolCount = $this->spool->stats()['count'];
            $afterStatus = $this->diagnostics->status();
            $afterRelayError = (string) ($afterStatus['last_relay_error'] ?? '');
            $beforeRelayError = (string) ($beforeStatus['last_relay_error'] ?? '');

            if ($afterSpoolCount > $beforeSpoolCount) {
                return new AdminTestResult(false, 'Frontend relay test event was queued for retry instead of being forwarded immediately. Check Last relay error in Status before expecting an incident.');
            }

            if ($afterRelayError !== '' && $afterRelayError !== $beforeRelayError) {
                return new AdminTestResult(false, 'Frontend relay test event hit a relay delivery error: ' . $afterRelayError);
            }

            return new AdminTestResult(true, 'Frontend relay test event sent to DebugBundle for service ' . $this->settings->getBrowserService() . '.');
        } catch (\Throwable $throwable) {
            Diagnostics::recordRelayError($throwable->getMessage());
            return new AdminTestResult(false, 'Frontend relay test event failed: ' . $this->safeErrorMessage($throwable));
        }
    }

    /** @return array<string, mixed> */
    private function browserTestEvent(): array
    {
        $browserSdkVersion = defined('DEBUGBUNDLE_WORDPRESS_BROWSER_SDK_VERSION')
            ? (string) constant('DEBUGBUNDLE_WORDPRESS_BROWSER_SDK_VERSION')
            : '0.1.8';
        $traceId = $this->uuidV4();
        $sessionId = $this->uuidV4();
        $route = '/wp-admin/options-general.php?page=debugbundle';

        return [
            'schema_version' => '2026-03-01',
            'event_id' => $this->uuidV4(),
            'event_type' => 'frontend_exception',
            'occurred_at' => gmdate('Y-m-d\TH:i:s') . 'Z',
            'sdk_name' => '@debugbundle/sdk-browser',
            'sdk_version' => $browserSdkVersion,
            'service' => [
                'name' => $this->settings->getBrowserService(),
                'runtime' => 'browser',
                'framework' => null,
                'environment' => $this->settings->getEnvironment(),
            ],
            'correlation' => [
                'request_id' => null,
                'trace_id' => $traceId,
                'session_id' => $sessionId,
                'user_id_hash' => null,
            ],
            'payload' => [
                'name' => 'DebugBundleWordPressFrontendTestError',
                'message' => 'DebugBundle WordPress frontend test event',
                'stack' => "DebugBundleWordPressFrontendTestError: DebugBundle WordPress frontend test event\n    at wordpress-admin-test.js:1:1",
                'route' => $route,
                'browser' => [
                    'name' => 'WordPress admin relay test',
                    'version' => $browserSdkVersion,
                ],
                'breadcrumbs' => [],
                'device' => null,
            ],
        ];
    }

    private function sameOrigin(): string
    {
        $url = $this->siteUrl('/');
        $parts = \wp_parse_url($url);
        if (!is_array($parts) || !isset($parts['scheme'], $parts['host'])) {
            return 'https://example.com';
        }

        $origin = $parts['scheme'] . '://' . $parts['host'];
        if (isset($parts['port'])) {
            $origin .= ':' . $parts['port'];
        }

        return $origin;
    }

    private function siteUrl(string $path): string
    {
        if (function_exists('home_url')) {
            $candidate = \home_url($path);
            if (is_string($candidate) && $candidate !== '') {
                return $candidate;
            }
        }

        return 'https://example.com' . $path;
    }

    private function responseStatus(mixed $response): int
    {
        if (is_object($response) && method_exists($response, 'get_status')) {
            $status = $response->get_status();
            return is_int($status) ? $status : 500;
        }

        if (is_object($response) && isset($response->status) && is_int($response->status)) {
            return $response->status;
        }

        if (is_array($response) && isset($response['status']) && is_int($response['status'])) {
            return $response['status'];
        }

        return 500;
    }

    /** @return array<string, mixed>|null */
    private function responseBody(mixed $response): ?array
    {
        if (is_object($response) && method_exists($response, 'get_data')) {
            $data = $response->get_data();
            return is_array($data) ? $data : null;
        }

        if (is_object($response) && isset($response->body) && is_array($response->body)) {
            return $response->body;
        }

        if (is_array($response) && isset($response['body']) && is_array($response['body'])) {
            return $response['body'];
        }

        return null;
    }

    /** @param array<string, mixed>|null $body */
    private function responseCount(?array $body, string $key): int
    {
        if ($body === null || !isset($body[$key])) {
            return 0;
        }

        return max(0, (int) $body[$key]);
    }

    /** @param array<string, mixed>|null $body
     *  @return list<string>
     */
    private function responseErrors(?array $body): array
    {
        if ($body === null || !isset($body['errors']) || !is_array($body['errors'])) {
            return [];
        }

        return array_values(array_filter($body['errors'], 'is_string'));
    }

    /** @param list<string> $errors */
    private function formatErrors(array $errors): string
    {
        if ($errors === []) {
            return 'unknown relay validation error';
        }

        return implode(' ', $errors);
    }

    private function safeErrorMessage(\Throwable $throwable): string
    {
        return substr($throwable->getMessage(), 0, 300);
    }

    private function uuidV4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
