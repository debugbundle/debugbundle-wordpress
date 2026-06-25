<?php

declare(strict_types=1);

namespace DebugBundleWp;

final class Settings
{
    public const OPTION_NAME = 'debugbundle_settings';
    public const PROJECT_TOKEN_CONSTANT = 'DEBUGBUNDLE_PROJECT_TOKEN';
    public const DEFAULT_ENDPOINT = 'https://api.debugbundle.com/v1/events';
    public const DEFAULT_RELAY_ROUTE = '/wp-json/debugbundle/v1/browser';
    private const SETTINGS_VERSION = 2;

    /** @return array<string, mixed> */
    public function all(): array
    {
        $stored = [];
        if (function_exists('get_option')) {
            $candidate = \get_option(self::OPTION_NAME, []);
            $stored = is_array($candidate) ? $candidate : [];
        }

        if ($stored !== [] && !array_key_exists('browser_load_in_head', $stored)) {
            $stored['browser_load_in_head'] = false;
        }

        return array_merge($this->defaults(), $stored);
    }

    /** @param array<string, mixed> $input
     *  @return array<string, mixed>
     */
    public function sanitize(array $input): array
    {
        $defaults = $this->defaults();

        $projectToken = $this->sanitizeText((string) ($input['project_token'] ?? ''));
        $environment = $this->sanitizeText((string) ($input['environment'] ?? ''));
        $service = $this->sanitizeText((string) ($input['service'] ?? ''));
        $endpoint = $this->sanitizeUrl((string) ($input['endpoint'] ?? ''));
        $logLevel = $this->sanitizeKey((string) ($input['log_level'] ?? 'warning'));
        $allowedLogLevels = ['debug', 'info', 'warning', 'error', 'critical'];

        $sanitized = [
            'enabled' => (bool) ($input['enabled'] ?? true),
            'project_token' => $projectToken,
            'environment' => $environment !== '' ? $environment : $defaults['environment'],
            'service' => $service !== '' ? $service : $defaults['service'],
            'endpoint' => $endpoint !== '' ? $endpoint : self::DEFAULT_ENDPOINT,
            'backend_capture_enabled' => (bool) ($input['backend_capture_enabled'] ?? true),
            'frontend_capture_enabled' => (bool) ($input['frontend_capture_enabled'] ?? true),
            'browser_load_in_head' => (bool) ($input['browser_load_in_head'] ?? false),
            'sample_rate' => $this->sanitizeSampleRate($input['sample_rate'] ?? 1.0),
            'browser_session_sample_rate' => $this->sanitizeSampleRate($input['browser_session_sample_rate'] ?? 1.0),
            'browser_max_events_per_session' => max(1, $this->sanitizePositiveInteger($input['browser_max_events_per_session'] ?? 100)),
            'browser_capture_console' => (bool) ($input['browser_capture_console'] ?? false),
            'log_level' => in_array($logLevel, $allowedLogLevels, true) ? $logLevel : 'warning',
            'delete_on_uninstall' => (bool) ($input['delete_on_uninstall'] ?? false),
            'settings_version' => self::SETTINGS_VERSION,
        ];

        return $sanitized;
    }

    public function isConfigured(): bool
    {
        return $this->getProjectToken() !== '';
    }

    public function isEnabled(): bool
    {
        return (bool) $this->all()['enabled'];
    }

    public function isBackendCaptureEnabled(): bool
    {
        return $this->isEnabled() && $this->isConfigured() && (bool) $this->all()['backend_capture_enabled'];
    }

    public function isFrontendCaptureEnabled(): bool
    {
        return $this->isEnabled() && $this->isConfigured() && (bool) $this->all()['frontend_capture_enabled'];
    }

    public function getProjectToken(): string
    {
        if (defined(self::PROJECT_TOKEN_CONSTANT)) {
            $constantValue = constant(self::PROJECT_TOKEN_CONSTANT);
            if (is_string($constantValue)) {
                return trim($constantValue);
            }
        }

        return trim((string) $this->all()['project_token']);
    }

    public function getEnvironment(): string
    {
        $environment = trim((string) $this->all()['environment']);
        if ($environment !== '') {
            return $environment;
        }

        if (function_exists('wp_get_environment_type')) {
            $candidate = \wp_get_environment_type();
            if (is_string($candidate) && $candidate !== '') {
                return $candidate;
            }
        }

        return 'production';
    }

    public function getService(): string
    {
        $service = trim((string) $this->all()['service']);
        return $service !== '' ? $service : $this->defaultServiceName();
    }

    public function getBrowserService(): string
    {
        return $this->getService() . '-browser';
    }

    public function getEndpoint(): string
    {
        $endpoint = trim((string) $this->all()['endpoint']);
        return $endpoint !== '' ? $endpoint : self::DEFAULT_ENDPOINT;
    }

    public function getRelayRoute(): string
    {
        return self::DEFAULT_RELAY_ROUTE;
    }

    public function getSampleRate(): float
    {
        return (float) $this->all()['sample_rate'];
    }

    public function getBrowserSessionSampleRate(): float
    {
        return (float) $this->all()['browser_session_sample_rate'];
    }

    public function getBrowserMaxEventsPerSession(): int
    {
        return (int) $this->all()['browser_max_events_per_session'];
    }

    public function getLogLevel(): string
    {
        return (string) $this->all()['log_level'];
    }

    public function shouldCaptureBrowserConsole(): bool
    {
        return (bool) $this->all()['browser_capture_console'];
    }

    public function shouldLoadBrowserInHead(): bool
    {
        return (bool) $this->all()['browser_load_in_head'];
    }

    public function shouldDeleteOnUninstall(): bool
    {
        return (bool) $this->all()['delete_on_uninstall'];
    }

    public function maskProjectToken(): string
    {
        $token = $this->getProjectToken();
        if ($token === '') {
            return '';
        }

        if (strlen($token) <= 8) {
            return str_repeat('*', strlen($token));
        }

        return substr($token, 0, 6) . str_repeat('*', max(4, strlen($token) - 10)) . substr($token, -4);
    }

    /** @return array<string, mixed> */
    public function frontendScriptConfig(): array
    {
        return [
            'endpoint' => $this->getRelayRoute(),
            'service' => $this->getBrowserService(),
            'environment' => $this->getEnvironment(),
            'enabled' => $this->isFrontendCaptureEnabled(),
            'breadcrumbsOnErrorOnly' => true,
            'captureNetwork' => true,
            'captureClicks' => true,
            'captureRouteChanges' => true,
            'captureConsole' => $this->shouldCaptureBrowserConsole(),
            'sessionSampleRate' => $this->getBrowserSessionSampleRate(),
            'maxEventsPerSession' => $this->getBrowserMaxEventsPerSession(),
            'networkFilter' => [
                'urlDenyPatterns' => [$this->getRelayRoute()],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function defaults(): array
    {
        return [
            'enabled' => true,
            'project_token' => '',
            'environment' => $this->defaultEnvironment(),
            'service' => $this->defaultServiceName(),
            'endpoint' => self::DEFAULT_ENDPOINT,
            'backend_capture_enabled' => true,
            'frontend_capture_enabled' => true,
            'browser_load_in_head' => true,
            'sample_rate' => 1.0,
            'browser_session_sample_rate' => 1.0,
            'browser_max_events_per_session' => 100,
            'browser_capture_console' => false,
            'log_level' => 'warning',
            'delete_on_uninstall' => false,
            'settings_version' => self::SETTINGS_VERSION,
        ];
    }

    private function defaultEnvironment(): string
    {
        if (function_exists('wp_get_environment_type')) {
            $candidate = \wp_get_environment_type();
            if (is_string($candidate) && $candidate !== '') {
                return $candidate;
            }
        }

        return 'production';
    }

    private function defaultServiceName(): string
    {
        $url = '';
        if (function_exists('home_url')) {
            $candidate = \home_url('/');
            $url = is_string($candidate) ? $candidate : '';
        }

        $host = \wp_parse_url($url, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            return 'wordpress-site';
        }

        return strtolower($host) . '-wordpress';
    }

    private function sanitizeText(string $value): string
    {
        if (function_exists('wp_unslash')) {
            $value = (string) \wp_unslash($value);
        }

        if (function_exists('sanitize_text_field')) {
            return \sanitize_text_field($value);
        }

        return $this->stripHtmlFallback($value);
    }

    private function sanitizeKey(string $value): string
    {
        if (function_exists('wp_unslash')) {
            $value = (string) \wp_unslash($value);
        }

        if (function_exists('sanitize_key')) {
            return \sanitize_key($value);
        }

        return preg_replace('/[^a-z0-9_\-]/', '', strtolower($value)) ?? '';
    }

    private function sanitizeUrl(string $value): string
    {
        if (function_exists('wp_unslash')) {
            $value = (string) \wp_unslash($value);
        }

        $sanitized = function_exists('esc_url_raw')
            ? \esc_url_raw($value)
            : filter_var(trim($value), FILTER_SANITIZE_URL);

        if (!is_string($sanitized) || $sanitized === '') {
            return '';
        }

        $scheme = \wp_parse_url($sanitized, PHP_URL_SCHEME);
        return in_array($scheme, ['http', 'https'], true) ? $sanitized : '';
    }

    private function sanitizePositiveInteger(mixed $value): int
    {
        if (function_exists('absint')) {
            return \absint($value);
        }

        return abs((int) $value);
    }

    private function sanitizeSampleRate(mixed $value): float
    {
        $rate = (float) $value;
        return min(1.0, max(0.0, $rate));
    }

    private function stripHtmlFallback(string $value): string
    {
        $withoutHtml = preg_replace('/<[^>]*>/', '', $value);
        return trim(is_string($withoutHtml) ? $withoutHtml : $value);
    }
}
