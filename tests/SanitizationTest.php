<?php

declare(strict_types=1);

namespace DebugBundleWp\Tests;

use DebugBundleWp\Sanitization;
use PHPUnit\Framework\TestCase;

final class SanitizationTest extends TestCase
{
    public function testRelayProtectsAcceptanceSecretsBeforeReturningEvents(): void
    {
        foreach (['invite', 'org_invite', 'org_transfer'] as $kind) {
            $event = ['event_type' => 'message_event', 'event_id' => '11111111-1111-4111-8111-111111111111',
                'sdk_name' => '@debugbundle/sdk-browser', 'sdk_version' => '3.2.1',
                'service' => ['name' => 'browser', 'environment' => 'test'],
                'payload' => ['message' => 'Accept dbundle_' . $kind . '_SYNTHETIC_SECRET retry']];
            $protected = Sanitization::protectRelayEvents([$event]);
            self::assertNotNull($protected);
            self::assertSame('Accept [REDACTED] retry', $protected[0]['payload']['message']);
        }
    }

    public function testRequestHeadersFromServerSanitizesHeaderNamesAndValues(): void
    {
        $headers = Sanitization::requestHeadersFromServer([
            'HTTP_X_DEBUGBUNDLE_TRACE_ID' => " trace-123\n",
            'HTTP_BAD_HEADER!' => 'ignored',
            'HTTP_X_SCRIPT' => '<script>alert(1)</script>',
            'CONTENT_TYPE' => 'application/json',
            'REMOTE_ADDR' => '203.0.113.10',
        ]);

        self::assertSame('trace-123', $headers['x-debugbundle-trace-id']);
        self::assertSame('alert(1)', $headers['x-script']);
        self::assertSame('application/json', $headers['content-type']);
        self::assertArrayNotHasKey('bad-header!', $headers);
        self::assertArrayNotHasKey('remote-addr', $headers);
    }

    public function testRequestFallbacksAndIpHashingRemainDeterministicOutsideHttpRuntime(): void
    {
        self::assertIsArray(Sanitization::serverInputArray());
        self::assertSame('PATCH', Sanitization::requestMethod('PATCH'));
        self::assertSame('/fallback', Sanitization::requestUri('/fallback'));
        self::assertNull(Sanitization::ipAddress());
        self::assertSame('POST', Sanitization::requestMethod('GET', ['REQUEST_METHOD' => 'post']));
        self::assertSame('/orders?status=open', Sanitization::requestUri('/', [
            'REQUEST_URI' => '/orders?status=open',
        ]));
        self::assertSame('203.0.113.10', Sanitization::ipAddress([
            'REMOTE_ADDR' => '203.0.113.10',
        ]));
        self::assertNull(Sanitization::ipAddress(['REMOTE_ADDR' => 'not-an-ip']));
        self::assertSame(hash('sha256', 'unknown'), Sanitization::hashIp(null));
        self::assertSame(hash('sha256', '203.0.113.10'), Sanitization::hashIp('203.0.113.10'));
    }

    public function testHeaderExtractionIgnoresNonScalarAndNonStringInputs(): void
    {
        $headers = Sanitization::requestHeadersFromServer([
            0 => 'ignored',
            'HTTP_ARRAY' => ['ignored'],
            'CONTENT_LENGTH' => 42,
            'REQUEST_METHOD' => 'POST',
        ]);

        self::assertSame(['content-length' => '42'], $headers);
    }

    public function testRelayPreservesValidatedBrowserResourceTagAndEvidenceReferenceWhileRedactingSecrets(): void
    {
        $failureRef = '11111111-1111-4111-8111-111111111111';
        $pageId = '22222222-2222-4222-8222-222222222222';
        $event = [
            'event_type' => 'frontend_exception',
            'event_id' => '33333333-3333-4333-8333-333333333333',
            'sdk_name' => '@debugbundle/sdk-browser',
            'sdk_version' => '3.2.4',
            'service' => ['name' => 'wp-browser', 'environment' => 'production'],
            'payload' => [
                'name' => 'Error',
                'message' => 'Browser resource load error',
                'stack' => 'unavailable',
                'browser_event' => [
                    'kind' => 'resource_error',
                    'opaque' => true,
                    'target' => [
                        'tag_name' => 'script',
                        'source_url' => 'https://cdn.example.com/app.js?token=SECRET_VALUE',
                    ],
                    'page' => [
                        'url' => 'https://shop.example.com/checkout?session_id=SECRET_SESS',
                        'referrer' => null,
                        'ready_state' => 'complete',
                        'visibility_state' => 'unloaded',
                    ],
                ],
            ],
            'context' => [
                'evidence_reference' => [
                    'version' => 1,
                    'failure_ref' => $failureRef,
                    'scope' => ['kind' => 'page', 'page_id' => $pageId],
                ],
                'password' => 'top-secret-password',
            ],
        ];

        $protected = Sanitization::protectRelayEvents([$event]);
        self::assertNotNull($protected);
        self::assertSame('script', $protected[0]['payload']['browser_event']['target']['tag_name']);
        self::assertSame('unloaded', $protected[0]['payload']['browser_event']['page']['visibility_state']);
        self::assertSame($failureRef, $protected[0]['context']['evidence_reference']['failure_ref']);
        self::assertSame('[REDACTED]', $protected[0]['context']['password']);
        self::assertStringNotContainsString('SECRET_VALUE', json_encode($protected, JSON_THROW_ON_ERROR));
        self::assertStringNotContainsString('SECRET_SESS', json_encode($protected, JSON_THROW_ON_ERROR));
    }
}
