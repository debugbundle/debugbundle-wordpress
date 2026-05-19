<?php

declare(strict_types=1);

namespace DebugBundleWp;

final class Sanitization
{
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

    public static function ipAddress(): ?string
    {
        $candidates = [
            $_SERVER['REMOTE_ADDR'] ?? null,
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
}
