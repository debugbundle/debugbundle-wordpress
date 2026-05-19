<?php

declare(strict_types=1);

namespace DebugBundleWp\Tests;

use DebugBundleWp\BrowserRelayRoute;
use DebugBundleWp\Settings;
use PHPUnit\Framework\TestCase;

final class BrowserRelayRouteTest extends TestCase
{
    public function testHandleRequestAcceptsWordPressRestUnderscoreContentTypeHeader(): void
    {
        if (!defined(Settings::PROJECT_TOKEN_CONSTANT)) {
            define(Settings::PROJECT_TOKEN_CONSTANT, 'dbundle_proj_test');
        }

        $route = new BrowserRelayRoute(new Settings(), 'debugbundle_test_cron');
        $response = $route->handleRequest(new class {
            /** @return array<string, string> */
            public function get_headers(): array
            {
                return [
                    'origin' => 'https://example.com',
                    'host' => 'example.com',
                    'content_type' => 'application/json',
                ];
            }

            public function get_body(): string
            {
                return json_encode([
                    'batch' => [[
                        'schema_version' => '2026-03-01',
                        'event_id' => '00000000-0000-4000-8000-000000000010',
                        'event_type' => 'frontend_exception',
                        'occurred_at' => '2026-05-19T00:00:00Z',
                        'sdk_version' => '0.1.7',
                        'service' => [
                            'name' => 'wordpress-test-browser',
                            'environment' => 'production',
                        ],
                        'payload' => [
                            'name' => 'FrontendTestError',
                            'message' => 'frontend test',
                            'stack' => 'FrontendTestError: frontend test',
                        ],
                    ]],
                ], JSON_THROW_ON_ERROR);
            }

            public function get_method(): string
            {
                return 'POST';
            }
        });

        self::assertIsArray($response);
        self::assertSame(202, $response['status']);
        self::assertSame(1, $response['body']['accepted']);
    }
}