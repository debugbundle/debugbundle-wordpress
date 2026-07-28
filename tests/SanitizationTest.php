<?php

declare(strict_types=1);

namespace DebugBundleWp\Tests;

use DebugBundleWp\Sanitization;
use PHPUnit\Framework\TestCase;

final class SanitizationTest extends TestCase
{
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
}
