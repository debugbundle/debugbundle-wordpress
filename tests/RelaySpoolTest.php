<?php

declare(strict_types=1);

namespace DebugBundleWp\Tests;

use DebugBundleWp\RelaySpool;
use PHPUnit\Framework\TestCase;

final class RelaySpoolTest extends TestCase
{
    private string $uploadBaseDir;

    protected function setUp(): void
    {
        $this->uploadBaseDir = sys_get_temp_dir() . '/debugbundle-relay-spool-' . bin2hex(random_bytes(6));
        $GLOBALS['debugbundle_wp_test_upload_basedir'] = $this->uploadBaseDir;
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->uploadBaseDir);
        unset($GLOBALS['debugbundle_wp_test_upload_basedir']);
    }

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

    public function testWriteListStatsDeleteAndAgePruning(): void
    {
        $spool = new RelaySpool();
        self::assertSame([], $spool->files());

        $written = $spool->write([['event_id' => 'evt-1']]);
        self::assertIsString($written);
        self::assertFileExists($written);
        self::assertSame(1, $spool->stats()['count']);
        self::assertGreaterThan(0, $spool->stats()['size']);

        touch($written, time() - 90_000);
        $spool->prune();
        self::assertFileDoesNotExist($written);

        $outside = $this->uploadBaseDir . '/outside.events.json';
        file_put_contents($outside, '{}');
        $spool->delete(sys_get_temp_dir() . '/not-in-spool.events.json');
        self::assertFileExists($outside);
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
