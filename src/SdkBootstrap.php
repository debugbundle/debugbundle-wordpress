<?php

declare(strict_types=1);

namespace DebugBundleWp;

use DebugBundle\DebugBundleSdk;

final class SdkBootstrap
{
    private ?DebugBundleSdk $sdk = null;
    private float $requestStartedAt;

    public function __construct(private readonly Settings $settings)
    {
        $this->requestStartedAt = microtime(true);
    }

    public function register(): void
    {
        if (!$this->settings->isBackendCaptureEnabled()) {
            return;
        }

        $this->sdk = new DebugBundleSdk();
        $this->sdk->init([
            'projectToken' => $this->settings->getProjectToken(),
            'endpoint' => $this->settings->getEndpoint(),
            'service' => $this->settings->getService(),
            'environment' => $this->settings->getEnvironment(),
            'sampleRate' => $this->settings->getSampleRate(),
            'logLevel' => $this->settings->getLogLevel(),
            'configFetcher' => new ConfigFetcher(),
            'redactFields' => ['wpnonce', '_wpnonce', 'woocommerce-login-nonce', 'woocommerce-register-nonce', 'woocommerce-reset-password-nonce'],
        ]);

        if (function_exists('add_action')) {
            \add_action('init', [$this, 'onInit'], 0);
            \add_action('shutdown', [$this, 'onShutdown'], 0);
        }
    }

    public function onInit(): void
    {
        if ($this->sdk === null) {
            return;
        }

        $this->sdk->beginRequest($this->buildRequestPayload());
    }

    public function onShutdown(): void
    {
        if ($this->sdk === null) {
            return;
        }

        $this->attachContext();
        $this->sdk->captureRequest($this->buildRequestPayload(), $this->buildResponsePayload());
        $this->sdk->flush();
        $this->sdk->endRequest();
    }

    private function attachContext(): void
    {
        if ($this->sdk === null) {
            return;
        }

        $this->sdk->setContext('wordpress', [
            'version' => function_exists('get_bloginfo') ? (string) \get_bloginfo('version') : null,
            'multisite' => function_exists('is_multisite') ? (bool) \is_multisite() : false,
            'theme' => $this->buildThemeContext(),
        ]);

        $userHash = $this->currentUserHash();
        if ($userHash !== null) {
            $this->sdk->setContext('user_id_hash', $userHash);
        }
    }

    /** @return array<string, mixed> */
    private function buildRequestPayload(): array
    {
        $query = [];
        foreach ($_GET as $key => $value) {
            if (is_string($key)) {
                $query[$key] = $value;
            }
        }

        return [
            'method' => isset($_SERVER['REQUEST_METHOD']) ? (string) $_SERVER['REQUEST_METHOD'] : 'GET',
            'path' => $this->requestPath(),
            'query' => $query,
            'headers' => $this->requestHeaders(),
        ];
    }

    /** @return array<string, mixed> */
    private function buildResponsePayload(): array
    {
        return [
            'status_code' => http_response_code() ?: 200,
            'duration_ms' => (int) round((microtime(true) - $this->requestStartedAt) * 1000),
        ];
    }

    private function requestPath(): string
    {
        $requestUri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';
        $path = parse_url($requestUri, PHP_URL_PATH);
        return is_string($path) && $path !== '' ? $path : '/';
    }

    /** @return array<string, string> */
    private function requestHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (!is_string($name) || !is_scalar($value)) {
                continue;
            }

            $headerName = null;
            if (str_starts_with($name, 'HTTP_')) {
                $headerName = str_replace('_', '-', strtolower(substr($name, 5)));
            } elseif (in_array($name, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true)) {
                $headerName = str_replace('_', '-', strtolower($name));
            }

            if ($headerName !== null) {
                $headers[$headerName] = (string) $value;
            }
        }

        return $headers;
    }

    /** @return array<string, mixed>|null */
    private function buildThemeContext(): ?array
    {
        if (!function_exists('wp_get_theme')) {
            return null;
        }

        $theme = \wp_get_theme();
        if (!is_object($theme) || !method_exists($theme, 'get')) {
            return null;
        }

        return [
            'name' => (string) $theme->get('Name'),
            'stylesheet' => method_exists($theme, 'get_stylesheet') ? (string) $theme->get_stylesheet() : null,
            'version' => (string) $theme->get('Version'),
        ];
    }

    private function currentUserHash(): ?string
    {
        if (!function_exists('get_current_user_id')) {
            return null;
        }

        $userId = (int) \get_current_user_id();
        if ($userId <= 0) {
            return null;
        }

        $salt = function_exists('wp_salt') ? (string) \wp_salt('auth') : 'debugbundle-wordpress';
        return hash_hmac('sha256', (string) $userId, $salt);
    }
}
