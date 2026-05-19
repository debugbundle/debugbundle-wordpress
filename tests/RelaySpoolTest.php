<?php

declare(strict_types=1);

namespace DebugBundleWp\Tests;

use DebugBundleWp\RelaySpool;
use PHPUnit\Framework\TestCase;

final class RelaySpoolTest extends TestCase
{
    public function testEnsureDirectoryWritesAccessProtectionFiles(): void
    {
        $spool = new RelaySpool();
        $directory = $spool->path();

        @unlink($directory . '/index.php');
        @unlink($directory . '/.htaccess');

        self::assertTrue($spool->ensureDirectory());
        self::assertFileExists($directory . '/index.php');
        self::assertFileExists($directory . '/.htaccess');
        self::assertStringContainsString('Require all denied', (string) file_get_contents($directory . '/.htaccess'));
    }
}