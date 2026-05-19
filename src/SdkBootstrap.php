<?php

declare(strict_types=1);

namespace DebugBundleWp;

if (!defined('ABSPATH')) {
    exit;
}

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

        try {
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
        } catch (\Throwable $throwable) {
            $this->sdk = null;
            $this->recordBackendDiagnostic($throwable);
            return;
        }

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

        try {
            $this->sdk->beginRequest($this->buildRequestPayload());
        } catch (\Throwable $throwable) {
            $this->recordBackendDiagnostic($throwable);
        }
    }

    public function onShutdown(): void
    {
        if ($this->sdk === null) {
            return;
        }

        try {
            $this->attachContext();
            $this->sdk->captureRequest($this->buildRequestPayload(), $this->buildResponsePayload());
            $this->sdk->flush();
            if ($this->sdk->getLastEventAt() !== null) {
                Diagnostics::recordBackendFlush($this->sdk->getLastEventAt());
            }
            $this->sdk->endRequest();
        } catch (\Throwable $throwable) {
            $this->recordBackendDiagnostic($throwable);
        }
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
        return [
            'method' => Sanitization::requestMethod('GET'),
            'path' => $this->requestPath(),
            'query' => $this->requestQuery(),
            'headers' => Sanitization::requestHeadersFromServer(Sanitization::serverInputArray()),
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
        $requestUri = Sanitization::requestUri('/');
        $path = \wp_parse_url($requestUri, PHP_URL_PATH);
        return is_string($path) && $path !== '' ? $path : '/';
    }

    /** @return array<string, mixed> */
    private function requestQuery(): array
    {
        $query = filter_input_array(INPUT_GET, FILTER_UNSAFE_RAW);
        if (!is_array($query)) {
            return [];
        }

        $normalized = [];
        foreach ($query as $name => $value) {
            if (!is_string($name)) {
                continue;
            }

            $normalized[$name] = $value;
        }

        return $normalized;
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

    private function recordBackendDiagnostic(\Throwable $throwable): void
    {
        Diagnostics::recordBackendError($throwable->getMessage());
    }
}
