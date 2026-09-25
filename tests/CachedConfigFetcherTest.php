<?php

declare(strict_types=1);

namespace DebugBundleWp\Tests;

use DebugBundleWp\CachedConfigFetcher;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class CachedConfigFetcherTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['debugbundle_wp_test_transients'] = [];
        unset($GLOBALS['debugbundle_wp_test_last_remote_request']);
    }

    public function testVisitorReadNeverFetchesAndMissingCacheFailsClosed(): void
    {
        $cache = new CachedConfigFetcher();
        try {
            $cache('https://api.example.test/v1/sdk/config', ['method' => 'GET']);
            self::fail('Expected missing config cache to fail.');
        } catch (RuntimeException $error) {
            self::assertSame('config_cache_unavailable', $error->getMessage());
        }
        self::assertArrayNotHasKey('debugbundle_wp_test_last_remote_request', $GLOBALS);
    }

    public function testCronRefreshMakesPolicyAvailableOnlyForMatchingEndpoint(): void
    {
        $GLOBALS['debugbundle_wp_test_remote_request_response'] = [
            'response' => ['code' => 200],
            'body' => '{"probes_enabled":true,"remote_probes_enabled":false,"active_probes":[]}',
        ];
        $cache = new CachedConfigFetcher();
        $cache->refresh('https://api.example.test/v1/events', 'dbundle_proj_test');

        self::assertSame('https://api.example.test/v1/sdk/config',
            $GLOBALS['debugbundle_wp_test_last_remote_request']['url']);
        self::assertSame(0.25, $GLOBALS['debugbundle_wp_test_last_remote_request']['args']['timeout']);
        self::assertSame('Bearer dbundle_proj_test',
            $GLOBALS['debugbundle_wp_test_last_remote_request']['args']['headers']['authorization']);
        self::assertStringNotContainsString('dbundle_proj_test',
            json_encode($GLOBALS['debugbundle_wp_test_transients'], JSON_THROW_ON_ERROR));
        $response = $cache('https://api.example.test/v1/sdk/config',
            ['headers' => ['authorization' => 'Bearer dbundle_proj_test']]);
        self::assertSame(200, $response->statusCode);
        self::assertSame(true, $response->json()['probes_enabled']);

        $this->expectException(RuntimeException::class);
        $cache('https://other.example.test/v1/sdk/config',
            ['headers' => ['authorization' => 'Bearer dbundle_proj_test']]);
    }

    public function testCachedPolicyCannotCrossProjectTokens(): void
    {
        $GLOBALS['debugbundle_wp_test_remote_request_response'] = [
            'response' => ['code' => 200],
            'body' => '{"probes_enabled":true,"remote_probes_enabled":false,"active_probes":[]}',
        ];
        $cache = new CachedConfigFetcher();
        $cache->refresh('https://api.example.test/v1/events', 'dbundle_proj_first');

        $this->expectException(RuntimeException::class);
        $cache('https://api.example.test/v1/sdk/config',
            ['headers' => ['authorization' => 'Bearer dbundle_proj_second']]);
    }
}
