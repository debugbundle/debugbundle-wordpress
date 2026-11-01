<?php

declare(strict_types=1);

namespace DebugBundleWp;

use DebugBundle\EvidenceWire;
use RuntimeException;

/** Reads only local WordPress state on a visitor request; remote refresh runs in WP-Cron. */
final class CachedConfigFetcher
{
    private const TRANSIENT = 'debugbundle_sdk_capture_config';

    /** @return array<string, mixed>|null */
    public function cachedPayload(string $eventEndpoint, string $projectToken): ?array
    {
        $url = self::configUrl($eventEndpoint);
        if ($url === null || $projectToken === '') {
            return null;
        }
        try {
            $payload = $this($url, ['headers' => ['authorization' => 'Bearer ' . $projectToken]])->json();
            return is_array($payload) ? $payload : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** A validated ingestion acknowledgement can update proof, never invent capture permission. */
    public function observeEvidence(string $eventEndpoint, string $projectToken, mixed $descriptor): void
    {
        $url = self::configUrl($eventEndpoint);
        if ($url === null || $projectToken === '' || !function_exists('set_transient')) {
            return;
        }
        $cached = $this->cachedPayload($eventEndpoint, $projectToken);
        $entry = function_exists('get_transient') ? \get_transient(self::TRANSIENT) : false;
        $expiresAt = $cached !== null && is_array($entry) && is_int($entry['policy_expires_at'] ?? null)
            ? $entry['policy_expires_at'] : 0;
        $payload = $cached ?? self::minimalPayload();
        $payload['evidence_capabilities'] = EvidenceWire::descriptor($descriptor);
        \set_transient(self::TRANSIENT, [
            'url' => $url, 'token_hash' => hash('sha256', 'Bearer ' . $projectToken),
            'payload' => $payload, 'policy_expires_at' => $expiresAt,
        ], 300);
    }

    /** @return array<string, mixed> */
    private static function minimalPayload(): array
    {
        return [
            'probes_enabled' => false, 'remote_probes_enabled' => false,
            'active_probes' => [], 'poll_interval_ms' => 60000,
            'capture_policy' => [
                'preset' => 'minimal', 'capture_logs' => 'error',
                'capture_request_events' => 'failures_only', 'capture_breadcrumbs' => 'local_only',
                'capture_probe_events' => 'buffer_only', 'capture_diagnostic_context' => false,
                'immediate_client_error_statuses' => [], 'immediate_client_error_path_rules' => [],
            ],
        ];
    }

    public function __invoke(string $url, array $request): ConfigFetchResponse
    {
        $entry = function_exists('get_transient') ? \get_transient(self::TRANSIENT) : false;
        $authorization = $request['headers']['authorization'] ?? null;
        // PHP's optional discovery query reads the same local policy/proof. Keep
        // all other URL differences outside this endpoint/token-scoped cache.
        $requestedUrl = str_ends_with($url, '?debugbundle_evidence=1')
            ? substr($url, 0, -strlen('?debugbundle_evidence=1')) : $url;
        if (!is_array($entry) || ($entry['url'] ?? null) !== $requestedUrl
            || !is_array($entry['payload'] ?? null)
            || !is_string($authorization)
            || !is_string($entry['token_hash'] ?? null)
            || !hash_equals($entry['token_hash'], hash('sha256', $authorization))) {
            throw new RuntimeException('config_cache_unavailable');
        }

        $payload = $entry['payload'];
        if (!is_int($entry['policy_expires_at'] ?? null) || $entry['policy_expires_at'] <= time()) {
            // ACK traffic can renew capability proof but never the config policy lease.
            $payload = array_replace($payload, self::minimalPayload());
            unset($payload['trigger_token_key']);
        }
        return new ConfigFetchResponse(200, [], $payload);
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
                'headers' => ['authorization' => $authorization, 'X-DebugBundle-Evidence' => '1'],
            ]);
            $payload = $response->json();
            if ($response->statusCode === 200 && is_array($payload) && function_exists('set_transient')) {
                \set_transient(self::TRANSIENT, [
                    'url' => $configUrl,
                    'token_hash' => hash('sha256', $authorization),
                    'payload' => $payload,
                    'policy_expires_at' => time() + 300,
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
