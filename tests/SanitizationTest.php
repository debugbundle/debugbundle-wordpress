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
}
