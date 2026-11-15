<?php
declare(strict_types=1);

namespace DebugBundleWp\Tests;

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__) . '/scripts/wordpress-health.php';

final class SmokeHealthTest extends TestCase
{
    public function testRootReadinessOnlyAcceptsHealthyAppOrInitialInstaller(): void
    {
        self::assertTrue(\debugbundleSmokeWordPressReady(['HTTP/1.1 200 OK']));
        self::assertTrue(\debugbundleSmokeWordPressReady(['HTTP/1.1 302 Found', 'Location: http://127.0.0.1:18080/wp-admin/install.php']));
        foreach ([[], ['HTTP/1.1 500 Internal Server Error'], ['HTTP/1.1 503 Unavailable'],
            ['HTTP/1.1 301 Moved', 'Location: http://127.0.0.1:18080/'],
            ['HTTP/1.1 302 Found', 'Location: http://127.0.0.1:18080/'], ['HTTP/1.1 302 Found']] as $headers) {
            self::assertFalse(\debugbundleSmokeWordPressReady($headers));
        }
    }
}
