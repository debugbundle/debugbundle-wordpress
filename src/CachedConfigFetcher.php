<?php

declare(strict_types=1);

namespace DebugBundleWp;

use RuntimeException;

/** Reads only local WordPress state on a visitor request; remote refresh runs in WP-Cron. */
final class CachedConfigFetcher
{
    private const TRANSIENT = 'debugbundle_sdk_capture_config';

    public function __invoke(string $url, array $request): ConfigFetchResponse
    {
        $entry = function_exists('get_transient') ? \get_transient(self::TRANSIENT) : false;
        $authorization = $request['headers']['authorization'] ?? null;
        if (!is_array($entry) || ($entry['url'] ?? null) !== $url
            || !is_array($entry['payload'] ?? null)
            || !is_string($authorization)
            || !is_string($entry['token_hash'] ?? null)
            || !hash_equals($entry['token_hash'], hash('sha256', $authorization))) {
            throw new RuntimeException('config_cache_unavailable');
        }

        return new ConfigFetchResponse(200, [], $entry['payload']);
    }

    public function refresh(string $eventEndpoint, string $projectToken): void
    {
        $configUrl = self::configUrl($eventEndpoint);
        if ($configUrl === null || $projectToken === '') {
            return;
        }
        $authorization = 'Bearer ' . $projectToken;
        try {
            $response = (new ConfigFetcher())($configUrl, [
                'method' => 'GET',
                'headers' => ['authorization' => $authorization],
            ]);
            $payload = $response->json();
            if ($response->statusCode === 200 && is_array($payload) && function_exists('set_transient')) {
                \set_transient(self::TRANSIENT, [
                    'url' => $configUrl,
                    'token_hash' => hash('sha256', $authorization),
                    'payload' => $payload,
                ], 300);
            }
        } catch (\Throwable) {
            // An unavailable remote policy leaves visitor requests on the minimal local policy.
        }
    }

    private static function configUrl(string $eventEndpoint): ?string
    {
        $parts = \wp_parse_url($eventEndpoint);
        if (!is_array($parts) || !in_array($parts['scheme'] ?? null, ['http', 'https'], true)
            || !is_string($parts['host'] ?? null)) {
            return null;
        }
        $path = rtrim((string) ($parts['path'] ?? ''), '/');
        $path = str_ends_with($path, '/events')
            ? substr($path, 0, -strlen('/events')) . '/sdk/config'
            : $path . '/sdk/config';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        return $parts['scheme'] . '://' . $parts['host'] . $port . $path;
    }
}
