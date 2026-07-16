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
}
