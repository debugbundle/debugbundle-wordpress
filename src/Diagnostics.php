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
        self::setStatus(self::LAST_BACKEND_ERROR, substr($message, 0, 500));
    }

    public static function recordRelayFlush(): void
    {
        self::setStatus(self::LAST_RELAY_FLUSH, self::formatTimestamp(null));
    }

    public static function recordRelayError(string $message): void
    {
        self::setStatus(self::LAST_RELAY_ERROR, substr($message, 0, 500));
    }

    public static function recordRelayIngestionResult(int $accepted, int $rejected, string $errors): void
    {
        self::setStatus(self::LAST_RELAY_INGESTION_RESULT, substr(sprintf('accepted=%d rejected=%d errors=%s', $accepted, $rejected, $errors), 0, 500));
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

        $value = \get_transient($key);
        return is_string($value) ? $value : '';
    }

    private static function setStatus(string $key, string $value): void
    {
        if (function_exists('set_transient')) {
            \set_transient($key, $value, 604800);
        }
    }

    private static function formatTimestamp(?float $timestampMs): string
    {
        $seconds = $timestampMs !== null ? (int) floor($timestampMs / 1000) : time();
        return gmdate('Y-m-d\TH:i:s', $seconds) . 'Z';
    }
}