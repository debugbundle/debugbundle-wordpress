<?php

declare(strict_types=1);

namespace DebugBundleWp;

final class Diagnostics
{
    private const LAST_BACKEND_FLUSH = 'debugbundle_last_backend_flush_at';
    private const LAST_BACKEND_ERROR = 'debugbundle_last_backend_error';
    private const LAST_RELAY_FLUSH = 'debugbundle_last_relay_flush_at';
    private const LAST_RELAY_ERROR = 'debugbundle_last_relay_error';
    private const LAST_RELAY_INGESTION_RESULT = 'debugbundle_last_relay_ingestion_result';
    private static bool $recording = false;

    public function __construct(private readonly RelaySpool $spool = new RelaySpool())
    {
    }

    /** @return array<string, string|int> */
    public function status(): array
    {
        $spoolStats = $this->spool->stats();

        return [
            'plugin_version' => defined('DEBUGBUNDLE_WORDPRESS_VERSION') ? (string) constant('DEBUGBUNDLE_WORDPRESS_VERSION') : 'unknown',
            'php_sdk_version' => $this->phpSdkVersion(),
            'browser_sdk_version' => defined('DEBUGBUNDLE_WORDPRESS_BROWSER_SDK_VERSION') ? (string) constant('DEBUGBUNDLE_WORDPRESS_BROWSER_SDK_VERSION') : 'unknown',
            'php_compatibility' => Compatibility::isPhpSupported() ? 'compatible' : 'unsupported',
            'last_backend_flush' => $this->transientString(self::LAST_BACKEND_FLUSH),
            'last_backend_error' => $this->transientString(self::LAST_BACKEND_ERROR),
            'last_relay_flush' => $this->transientString(self::LAST_RELAY_FLUSH),
            'last_relay_error' => $this->transientString(self::LAST_RELAY_ERROR),
            'last_relay_ingestion_result' => $this->transientString(self::LAST_RELAY_INGESTION_RESULT),
            'spool_file_count' => $spoolStats['count'],
            'spool_size_bytes' => $spoolStats['size'],
        ];
    }

    public static function recordBackendFlush(?float $timestampMs = null): void
    {
        self::setStatus(self::LAST_BACKEND_FLUSH, self::formatTimestamp($timestampMs));
    }

    public static function recordBackendError(string $message): void
    {
        self::setStatus(self::LAST_BACKEND_ERROR, $message);
    }

    public static function recordRelayFlush(): void
    {
        self::setStatus(self::LAST_RELAY_FLUSH, self::formatTimestamp(null));
    }

    public static function recordRelayError(string $message): void
    {
        self::setStatus(self::LAST_RELAY_ERROR, $message);
    }

    public static function recordRelayIngestionResult(int $accepted, int $rejected, string $errors): void
    {
        self::setStatus(self::LAST_RELAY_INGESTION_RESULT, sprintf('accepted=%d rejected=%d errors=%s', $accepted, $rejected, $errors));
    }

    private function phpSdkVersion(): string
    {
        if (class_exists('\Composer\InstalledVersions')) {
            $version = \Composer\InstalledVersions::getPrettyVersion('debugbundle/sdk-php');
            if (is_string($version) && $version !== '') {
                return $version;
            }
        }

        return 'bundled';
    }

    private function transientString(string $key): string
    {
        if (!function_exists('get_transient')) {
            return '';
        }

        try {
            $value = \get_transient($key);
            // Protect legacy values at the read boundary without rewriting data.
            return is_string($value) ? self::safeValue($value) : '';
        } catch (\Throwable) {
            return '';
        }
    }

    private static function setStatus(string $key, string $value): void
    {
        if (self::$recording || !function_exists('set_transient')) return;
        self::$recording = true;
        try {
            \set_transient($key, self::safeValue($value), 604800);
        } catch (\Throwable) {
            // Diagnostic privacy/storage failures cannot escape an error handler.
        } finally {
            self::$recording = false;
        }
    }

    private static function safeValue(string $value): string
    {
        // Sanitize before truncation so a cut credential is never unchecked.
        $safe = strlen($value) <= 4096 ? \DebugBundle\TelemetryPrivacy::protect($value, array_fill_keys(Sanitization::REDACT_FIELDS, true)) : 'diagnostic_details_withheld';
        $safe = is_string($safe) ? substr($safe, 0, 500) : 'diagnostic_details_withheld';
        for ($i = 0; $i < 4 && preg_match('//u', $safe) !== 1; $i++) $safe = substr($safe, 0, -1);
        return $safe;
    }

    private static function formatTimestamp(?float $timestampMs): string
    {
        $seconds = $timestampMs !== null ? (int) floor($timestampMs / 1000) : time();
        return gmdate('Y-m-d\TH:i:s', $seconds) . 'Z';
    }
}
