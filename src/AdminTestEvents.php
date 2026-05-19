<?php

declare(strict_types=1);

namespace DebugBundleWp;

use DebugBundle\DebugBundleSdk;

final class AdminTestEvents
{
    public function __construct(private readonly Settings $settings)
    {
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
            $route = new BrowserRelayRoute($this->settings, 'debugbundle_admin_test_relay');
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
            if ($status >= 200 && $status < 300) {
                return new AdminTestResult(true, 'Frontend relay test event sent to DebugBundle.');
            }

            Diagnostics::recordRelayError('Frontend relay test event rejected with status ' . $status . '.');
            return new AdminTestResult(false, 'Frontend relay test event was rejected with status ' . $status . '.');
        } catch (\Throwable $throwable) {
            Diagnostics::recordRelayError($throwable->getMessage());
            return new AdminTestResult(false, 'Frontend relay test event failed: ' . $this->safeErrorMessage($throwable));
        }
    }

    /** @return array<string, mixed> */
    private function browserTestEvent(): array
    {
        return [
            'schema_version' => '2026-03-01',
            'event_id' => $this->uuidV4(),
            'event_type' => 'frontend_exception',
            'occurred_at' => gmdate('Y-m-d\TH:i:s') . 'Z',
            'sdk_name' => '@debugbundle/sdk-browser',
            'sdk_version' => '0.1.7',
            'service' => [
                'name' => $this->settings->getBrowserService(),
                'environment' => $this->settings->getEnvironment(),
            ],
            'correlation' => [
                'trace_id' => $this->uuidV4(),
            ],
            'payload' => [
                'name' => 'DebugBundleWordPressFrontendTestError',
                'message' => 'DebugBundle WordPress frontend test event',
                'stack' => 'DebugBundleWordPressFrontendTestError: DebugBundle WordPress frontend test event',
                'url' => $this->siteUrl('/'),
                'breadcrumbs' => [],
            ],
        ];
    }

    private function sameOrigin(): string
    {
        $url = $this->siteUrl('/');
        $parts = parse_url($url);
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