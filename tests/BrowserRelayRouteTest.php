<?php

declare(strict_types=1);

namespace DebugBundleWp\Tests;

use DebugBundleWp\BrowserRelayRoute;
use DebugBundleWp\Settings;
use PHPUnit\Framework\TestCase;

final class BrowserRelayRouteTest extends TestCase
{
    /** @var array{version:int,cases:list<array<string,mixed>>}|null */
    private static ?array $relayComplianceFixtures = null;
    private string $uploadBaseDir;

    protected function setUp(): void
    {
        $this->uploadBaseDir = sys_get_temp_dir() . '/debugbundle-browser-route-' . bin2hex(random_bytes(6));
        $GLOBALS['debugbundle_wp_test_upload_basedir'] = $this->uploadBaseDir;
        $GLOBALS['debugbundle_wp_test_options'] = [
            Settings::OPTION_NAME => [
                'enabled' => true,
                'project_token' => 'dbundle_proj_test',
                'environment' => 'production',
                'service' => 'wordpress-test',
                'frontend_capture_enabled' => true,
            ],
        ];
        $GLOBALS['debugbundle_wp_test_remote_response'] = [
            'response' => ['code' => 202],
            'body' => json_encode(['accepted' => 1, 'rejected' => 0, 'errors' => []]),
        ];
        $GLOBALS['debugbundle_wp_test_rest_routes'] = [];
        $GLOBALS['debugbundle_wp_test_actions'] = [];
        $GLOBALS['debugbundle_wp_test_scheduled_hooks'] = [];
    }

    protected function tearDown(): void
    {
        $this->removeDirectory((new \DebugBundleWp\RelaySpool())->path());
        $this->removeDirectory($this->uploadBaseDir);
        unset(
            $GLOBALS['debugbundle_wp_test_upload_basedir'],
            $GLOBALS['debugbundle_wp_test_options'],
            $GLOBALS['debugbundle_wp_test_remote_response'],
            $GLOBALS['debugbundle_wp_test_scheduled_hooks']
        );
    }

    public function testRouteRegistersRestCallbackAndHonorsDisabledCapture(): void
    {
        $route = new BrowserRelayRoute(new Settings(), 'debugbundle_test_cron');
        $route->register();
        $route->registerRoute();

        self::assertNotEmpty($GLOBALS['debugbundle_wp_test_actions']['rest_api_init'][10] ?? []);
        self::assertSame('debugbundle/v1', $GLOBALS['debugbundle_wp_test_rest_routes'][0][0] ?? null);
        self::assertSame('/browser', $GLOBALS['debugbundle_wp_test_rest_routes'][0][1] ?? null);

        $GLOBALS['debugbundle_wp_test_options'][Settings::OPTION_NAME]['enabled'] = false;
        $response = $route->handleRequest($this->createWordPressRelayRequest(
            ['origin' => 'https://example.com', 'host' => 'example.com'],
            '{"batch":[]}',
            'POST'
        ));
        self::assertSame(404, $response['status']);
        self::assertSame(['frontend_capture_disabled'], $response['body']['errors']);
    }

    public function testRouteRejectsCrossOriginPreflight(): void
    {
        $route = new BrowserRelayRoute(new Settings(), 'debugbundle_test_cron');

        $response = $route->handleRequest($this->createWordPressRelayRequest([
            'origin' => 'https://evil.example.net',
            'host' => 'app.example.com',
        ], '', 'OPTIONS'));

        self::assertSame(403, $response['status']);
    }

    public function testPartialAcknowledgementReplacesSpoolWithOnlyRetryableEvent(): void
    {
        $fixture = self::relayComplianceFixture('valid-browser-batch');
        $events = $fixture['request']['bodyJson']['batch'];
        $second = $events[0];
        $second['event_id'] = '00000000-0000-4000-8000-000000000099';
        $events[] = $second;
        $GLOBALS['debugbundle_wp_test_remote_response'] = [
            'response' => ['code' => 202],
            'body' => json_encode([
                'accepted' => 1,
                'rejected' => 1,
                'errors' => [['index' => 1, 'reason' => 'rate_limited']],
            ]),
        ];
        $request = $fixture['request'];
        $request['bodyJson']['batch'] = $events;
        $route = new BrowserRelayRoute(new Settings(), 'debugbundle_test_cron');

        $response = $route->handleRequest($this->createWordPressRelayRequestFromFixture($request));

        self::assertSame(202, $response['status']);
        $files = (new \DebugBundleWp\RelaySpool())->files();
        self::assertCount(1, $files);
        $spooled = json_decode((string) file_get_contents($files[0]), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(
            '00000000-0000-4000-8000-000000000099',
            $spooled['events'][0]['event_id']
        );
        self::assertArrayHasKey('debugbundle_test_cron', $GLOBALS['debugbundle_wp_test_scheduled_hooks']);
    }

    public function testRetryableHttpFailureRetainsOriginalSpoolBatch(): void
    {
        $fixture = self::relayComplianceFixture('valid-browser-batch');
        $GLOBALS['debugbundle_wp_test_remote_response'] = [
            'response' => ['code' => 503],
            'body' => '',
        ];
        $route = new BrowserRelayRoute(new Settings(), 'debugbundle_test_cron');

        $response = $route->handleRequest($this->createWordPressRelayRequestFromFixture($fixture['request']));

        self::assertSame(202, $response['status']);
        $files = (new \DebugBundleWp\RelaySpool())->files();
        self::assertCount(1, $files);
        $spooled = json_decode((string) file_get_contents($files[0]), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(
            $fixture['request']['bodyJson']['batch'][0]['event_id'],
            $spooled['events'][0]['event_id']
        );
        self::assertArrayHasKey('debugbundle_test_cron', $GLOBALS['debugbundle_wp_test_scheduled_hooks']);
    }

    public function testPreUpgradeSpoolAndForwardingScrubBeforeRetry(): void
    {
        $fixture = self::relayComplianceFixture('valid-browser-batch');
        $event = $fixture['request']['bodyJson']['batch'][0];
        $event['payload']['message'] = 'password=raw-canary-secret';
        $spool = new \DebugBundleWp\RelaySpool();
        self::assertTrue($spool->ensureDirectory());
        file_put_contents($spool->path() . '/old.events.json', json_encode(['events' => [$event]], JSON_THROW_ON_ERROR));
        $GLOBALS['debugbundle_wp_test_remote_response'] = ['response' => ['code' => 503], 'body' => ''];

        (new BrowserRelayRoute(new Settings(), 'debugbundle_test_cron'))->flushSpool();

        $files = $spool->files();
        self::assertCount(1, $files);
        self::assertStringNotContainsString('raw-canary-secret', (string) file_get_contents($files[0]));
        self::assertStringNotContainsString('raw-canary-secret', (string) ($GLOBALS['debugbundle_wp_test_last_remote_post']['args']['body'] ?? ''));
    }

    public function testUploadsSpoolIsMovedIntoPrivateStorageBeforeDelivery(): void
    {
        $fixture = self::relayComplianceFixture('valid-browser-batch');
        $event = $fixture['request']['bodyJson']['batch'][0];
        $event['payload']['message'] = 'password=raw-legacy-secret';
        $legacy = $this->uploadBaseDir . '/debugbundle-spool';
        mkdir($legacy, 0700, true);
        file_put_contents($legacy . '/old.events.json', json_encode(['events' => [$event]], JSON_THROW_ON_ERROR));
        $GLOBALS['debugbundle_wp_test_remote_response'] = ['response' => ['code' => 503], 'body' => ''];

        (new BrowserRelayRoute(new Settings(), 'debugbundle_test_cron'))->flushSpool();

        self::assertFileDoesNotExist($legacy . '/old.events.json');
        $spool = new \DebugBundleWp\RelaySpool();
        self::assertStringNotContainsString($this->uploadBaseDir, $spool->path());
        self::assertCount(1, $spool->files());
        self::assertStringNotContainsString('raw-legacy-secret', (string) file_get_contents($spool->files()[0]));
        self::assertStringNotContainsString('raw-legacy-secret', (string) ($GLOBALS['debugbundle_wp_test_last_remote_post']['args']['body'] ?? ''));
    }

    public function testFlushSpoolDropsUnreadableMalformedAndTerminallyRejectedFiles(): void
    {
        $spool = new \DebugBundleWp\RelaySpool();
        self::assertTrue($spool->ensureDirectory());
        file_put_contents($spool->path() . '/001.events.json', '');
        file_put_contents($spool->path() . '/002.events.json', '{bad-json');
        $fixture = self::relayComplianceFixture('valid-browser-batch');
        file_put_contents(
            $spool->path() . '/003.events.json',
            json_encode(['events' => $fixture['request']['bodyJson']['batch']], JSON_THROW_ON_ERROR)
        );
        $GLOBALS['debugbundle_wp_test_remote_response'] = [
            'response' => ['code' => 202],
            'body' => json_encode([
                'accepted' => 0,
                'rejected' => 1,
                'errors' => [['index' => 0, 'reason' => 'invalid_event']],
            ]),
        ];

        (new BrowserRelayRoute(new Settings(), 'debugbundle_test_cron'))->flushSpool();

        self::assertSame([], $spool->files());
    }

    public function testHandleRequestAcceptsAnalyticsEventsThroughThePhpRelay(): void
    {
        $fixture = self::relayComplianceFixture('valid-analytics-event');
        if (!defined(Settings::PROJECT_TOKEN_CONSTANT)) {
            define(Settings::PROJECT_TOKEN_CONSTANT, 'dbundle_proj_test');
        }

        $route = new BrowserRelayRoute(new Settings(), 'debugbundle_test_cron');
        $response = $route->handleRequest($this->createWordPressRelayRequestFromFixture($fixture['request']));

        self::assertIsArray($response);
        self::assertSame(202, $response['status']);
        self::assertSame(1, $response['body']['accepted']);
        self::assertSame(0, $response['body']['rejected']);
    }

    public function testHandleRequestAcceptsWordPressRestUnderscoreContentTypeHeader(): void
    {
        $fixture = self::relayComplianceFixture('valid-browser-batch');

        if (!defined(Settings::PROJECT_TOKEN_CONSTANT)) {
            define(Settings::PROJECT_TOKEN_CONSTANT, 'dbundle_proj_test');
        }

        $route = new BrowserRelayRoute(new Settings(), 'debugbundle_test_cron');
        $response = $route->handleRequest($this->createWordPressRelayRequestFromFixture($fixture['request']));

        self::assertIsArray($response);
        self::assertSame($fixture['expected']['status'], $response['status']);
        self::assertSame($fixture['expected']['accepted'], $response['body']['accepted']);
        self::assertSame($fixture['expected']['rejected'], $response['body']['rejected']);
        self::assertSame($fixture['expected']['errors'], $response['body']['errors']);
    }

    public function testHandleRequestUsesSharedFixtureForMixedValidAndInvalidBatch(): void
    {
        $fixture = self::relayComplianceFixture('mixed-valid-invalid-batch');

        if (!defined(Settings::PROJECT_TOKEN_CONSTANT)) {
            define(Settings::PROJECT_TOKEN_CONSTANT, 'dbundle_proj_test');
        }

        $route = new BrowserRelayRoute(new Settings(), 'debugbundle_test_cron');
        $response = $route->handleRequest($this->createWordPressRelayRequestFromFixture($fixture['request']));

        self::assertIsArray($response);
        self::assertSame($fixture['expected']['status'], $response['status']);
        self::assertSame($fixture['expected']['accepted'], $response['body']['accepted']);
        self::assertSame($fixture['expected']['rejected'], $response['body']['rejected']);
        self::assertSame($fixture['expected']['errors'], $response['body']['errors']);
    }

    public function testHandleRequestAnswersAllowedPreflight(): void
    {
        if (!defined(Settings::PROJECT_TOKEN_CONSTANT)) {
            define(Settings::PROJECT_TOKEN_CONSTANT, 'dbundle_proj_test');
        }

        $route = new BrowserRelayRoute(new Settings(), 'debugbundle_test_cron');
        $response = $route->handleRequest($this->createWordPressRelayRequest([
            'origin' => 'https://example.com',
            'host' => 'example.com',
            'access-control-request-method' => 'POST',
            'access-control-request-headers' => 'content-type',
        ], '', 'OPTIONS'));

        self::assertIsArray($response);
        self::assertSame(204, $response['status']);
        self::assertSame('https://example.com', $response['headers']['Access-Control-Allow-Origin']);
        self::assertSame('POST, OPTIONS', $response['headers']['Access-Control-Allow-Methods']);
    }

    /** @param array<string, mixed> $request */
    private function createWordPressRelayRequestFromFixture(array $request): object
    {
        $headers = $request['headers'] ?? [];
        if (isset($headers['content-type'])) {
            $headers['content_type'] = $headers['content-type'];
            unset($headers['content-type']);
        }

        $body = isset($request['bodyText'])
            ? (string) $request['bodyText']
            : json_encode($request['bodyJson'] ?? ['batch' => []], JSON_THROW_ON_ERROR);
        $method = (string) ($request['method'] ?? 'POST');

        return $this->createWordPressRelayRequest($headers, $body, $method);
    }

    /** @param array<string, string> $headers */
    private function createWordPressRelayRequest(array $headers, string $body, string $method): object
    {
        return new class($headers, $body, $method) {
            /** @param array<string, string> $headers */
            public function __construct(
                private readonly array $headers,
                private readonly string $body,
                private readonly string $method,
            ) {
            }

            /** @return array<string, string> */
            public function get_headers(): array
            {
                return $this->headers;
            }

            public function get_body(): string
            {
                return $this->body;
            }

            public function get_method(): string
            {
                return $this->method;
            }
        };
    }

    /** @return array<string, mixed> */
    private static function relayComplianceFixture(string $fixtureId): array
    {
        if (self::$relayComplianceFixtures === null) {
            /** @var array{version:int,cases:list<array<string,mixed>>} $decoded */
            $decoded = json_decode((string) file_get_contents(__DIR__ . '/fixtures/relay-compliance.json'), true, 512, JSON_THROW_ON_ERROR);
            self::$relayComplianceFixtures = $decoded;
        }

        foreach (self::$relayComplianceFixtures['cases'] as $fixture) {
            if (($fixture['id'] ?? null) === $fixtureId) {
                return $fixture;
            }
        }

        throw new \RuntimeException('Missing relay compliance fixture: ' . $fixtureId);
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        foreach (scandir($path) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $child = $path . DIRECTORY_SEPARATOR . $item;
            if (is_dir($child)) {
                $this->removeDirectory($child);
            } else {
                @unlink($child);
            }
        }
        @rmdir($path);
    }
}
