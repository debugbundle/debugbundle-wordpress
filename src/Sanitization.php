<?php

declare(strict_types=1);

namespace DebugBundleWp;

final class Sanitization
{
    /** @return array<string, mixed> */
    public static function serverInputArray(): array
    {
        $server = filter_input_array(INPUT_SERVER, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        return is_array($server) ? $server : [];
    }

    /** @param array<string, mixed> $server
     *  @return array<string, string>
     */
    public static function requestHeadersFromServer(array $server): array
    {
        $headers = [];
        foreach ($server as $name => $value) {
            if (!is_string($name) || !is_scalar($value)) {
                continue;
            }

            $headerName = null;
            $serverName = strtoupper($name);
            if (!preg_match('/^[A-Z0-9_]+$/', $serverName)) {
                continue;
            }

            if (str_starts_with($serverName, 'HTTP_')) {
                $headerName = str_replace('_', '-', strtolower(substr($serverName, 5)));
            } elseif (in_array($serverName, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true)) {
                $headerName = str_replace('_', '-', strtolower($serverName));
            }

            if ($headerName !== null && preg_match('/^[a-z0-9-]+$/', $headerName) === 1) {
                $headers[$headerName] = self::sanitizeText((string) $value);
            }
        }

        return $headers;
    }

    public static function requestMethod(string $default): string
    {
        $method = self::serverValue('REQUEST_METHOD');
        if ($method === null || $method === '') {
            return $default;
        }

        return strtoupper($method);
    }

    public static function requestUri(string $default): string
    {
        $requestUri = self::serverValue('REQUEST_URI');
        if ($requestUri === null || $requestUri === '') {
            return $default;
        }

        return $requestUri;
    }

    public static function ipAddress(): ?string
    {
        $candidates = [
            self::serverValue('REMOTE_ADDR'),
        ];

        foreach ($candidates as $candidate) {
            if (!is_string($candidate) || $candidate === '') {
                continue;
            }

            $validated = filter_var($candidate, FILTER_VALIDATE_IP);
            if (is_string($validated)) {
                return $validated;
            }
        }

        return null;
    }

    public static function hashIp(?string $ipAddress): string
    {
        return hash('sha256', $ipAddress ?? 'unknown');
    }

    private static function serverValue(string $key): ?string
    {
        $server = self::serverInputArray();
        $candidate = $server[$key] ?? null;
        if (!is_scalar($candidate)) {
            return null;
        }

        $value = (string) $candidate;
        if (function_exists('wp_unslash')) {
            $value = (string) \wp_unslash($value);
        }

        return $value;
    }

    private static function sanitizeText(string $value): string
    {
        if (function_exists('wp_unslash')) {
            $value = (string) \wp_unslash($value);
        }

        if (function_exists('sanitize_text_field')) {
            return (string) \sanitize_text_field($value);
        }

        $withoutHtml = preg_replace('/<[^>]*>/', '', $value);
        return trim(is_string($withoutHtml) ? $withoutHtml : $value);
    }
}
