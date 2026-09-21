<?php

declare(strict_types=1);

namespace DebugBundleWp;

use DebugBundle\TelemetryPrivacy;

final class Sanitization
{
    /** @param list<array<string, mixed>> $events
     *  @return list<array<string, mixed>>|null
     */
    public static function protectRelayEvents(array $events): ?array
    {
        if (count($events) > 256) {
            return null;
        }

        try {
            $safe = [];
            foreach ($events as $event) {
                if (!TelemetryPrivacy::hasSafeEventIdentity($event)) {
                    return null;
                }
                if (!is_array($event['service'] ?? null) || !is_array($event['payload'] ?? null)) {
                    return null;
                }
                $protected = TelemetryPrivacy::protect([
                    'service' => $event['service'],
                    'payload' => $event['payload'],
                ]);
                if (!is_array($protected) || !is_array($protected['service'] ?? null)
                    || !is_array($protected['payload'] ?? null)) {
                    return null;
                }
                $event['service'] = $protected['service'];
                $event['payload'] = $protected['payload'];
                if (isset($event['correlation']) && is_array($event['correlation'])) {
                    foreach ($event['correlation'] as $key => $value) {
                        if (is_string($value)) {
                            $event['correlation'][$key] = TelemetryPrivacy::protect($value);
                        }
                    }
                }
                $safe[] = $event;
            }
            return $safe;
        } catch (\Throwable) {
            // Old or missing SDK dependencies cannot turn a relay retry into raw egress.
            return null;
        }
    }
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

    /** @param array<string, mixed>|null $server */
    public static function requestMethod(string $default, ?array $server = null): string
    {
        $method = self::serverValue('REQUEST_METHOD', $server);
        if ($method === null || $method === '') {
            return $default;
        }

        return strtoupper($method);
    }

    /** @param array<string, mixed>|null $server */
    public static function requestUri(string $default, ?array $server = null): string
    {
        $requestUri = self::serverValue('REQUEST_URI', $server);
        if ($requestUri === null || $requestUri === '') {
            return $default;
        }

        return $requestUri;
    }

    /** @param array<string, mixed>|null $server */
    public static function ipAddress(?array $server = null): ?string
    {
        $candidates = [
            self::serverValue('REMOTE_ADDR', $server),
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

    /** @param array<string, mixed>|null $server */
    private static function serverValue(string $key, ?array $server = null): ?string
    {
        $server ??= self::serverInputArray();
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
