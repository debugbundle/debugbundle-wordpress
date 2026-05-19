<?php

declare(strict_types=1);

namespace DebugBundleWp;

final class Sanitization
{
    /** @return array<string, mixed> */
    public static function serverInputArray(): array
    {
        $server = filter_input_array(INPUT_SERVER, FILTER_UNSAFE_RAW);
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
}
