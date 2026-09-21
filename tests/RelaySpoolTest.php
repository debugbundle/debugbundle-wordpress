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
        $this->removeDirectory((new RelaySpool())->path());
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

    public function testPrivateSpoolRejectsAReplacedDirectorySymlink(): void
    {
        $spool = new RelaySpool();
        $path = $spool->path();
        mkdir($this->uploadBaseDir, 0700, true);
        symlink($this->uploadBaseDir, $path);
        try {
            self::assertFalse($spool->ensureDirectory());
            self::assertNull($spool->write([['event_id' => 'evt-symlink',
                'service' => ['name' => 'wp', 'environment' => 'test'],
                'payload' => ['message' => 'password=canary']]]));
            self::assertSame(['.', '..'], scandir($this->uploadBaseDir));
        } finally {
            unlink($path);
        }
    }

    public function testWriteListStatsDeleteAndAgePruning(): void
    {
        $spool = new RelaySpool();
        self::assertSame([], $spool->files());

        $written = $spool->write([['event_id' => 'evt-1',
            'service' => ['name' => 'wp', 'environment' => 'test'],
            'payload' => ['message' => 'password=raw-canary-secret']]]);
        self::assertIsString($written);
        self::assertFileExists($written);
        self::assertStringNotContainsString('raw-canary-secret', (string) file_get_contents($written));
        self::assertSame(1, $spool->stats()['count']);
        self::assertGreaterThan(0, $spool->stats()['size']);

        touch($written, time() - 90_000);
        $spool->prune();
        self::assertFileDoesNotExist($written);

        $outside = $this->uploadBaseDir . '/outside.events.json';
        mkdir($this->uploadBaseDir, 0700, true);
        file_put_contents($outside, '{}');
        $spool->delete(sys_get_temp_dir() . '/not-in-spool.events.json');
        self::assertFileExists($outside);
    }

    public function testProtectedReadRejectsOutsideFilesAndRewritesSafeSpoolContent(): void
    {
        $spool = new RelaySpool();
        self::assertNull($spool->readProtected($this->uploadBaseDir . '/outside.events.json'));

        self::assertTrue($spool->ensureDirectory());
        $file = $spool->path() . '/protected.events.json';
        file_put_contents($file, json_encode([
            'events' => [[
                'event_id' => 'evt-protected-read',
                'service' => ['name' => 'wp', 'environment' => 'test'],
                'payload' => ['message' => 'password=raw-protected-read-canary'],
            ]],
        ], JSON_THROW_ON_ERROR));

        $events = $spool->readProtected($file);

        self::assertIsArray($events);
        self::assertSame('evt-protected-read', $events[0]['event_id']);
        self::assertStringNotContainsString('raw-protected-read-canary', (string) file_get_contents($file));
        self::assertSame(0600, fileperms($file) & 0777);
    }

    public function testPruneEnforcesTheTotalPrivateSpoolBound(): void
    {
        $spool = new RelaySpool();
        self::assertTrue($spool->ensureDirectory());
        for ($index = 0; $index < 42; $index++) {
            $file = $spool->path() . sprintf('/%03d.events.json', $index);
            file_put_contents($file, str_repeat('x', 262_000));
            touch($file, time() - 100 + $index);
        }

        self::assertGreaterThan(10_485_760, $spool->stats()['size']);
        $spool->prune();

        self::assertLessThanOrEqual(10_485_760, $spool->stats()['size']);
        self::assertFileDoesNotExist($spool->path() . '/000.events.json');
        self::assertFileExists($spool->path() . '/041.events.json');
    }

    public function testDeleteCannotEscapeThroughASpoolPathPrefix(): void
    {
        $spool = new RelaySpool();
        $outside = $spool->path() . '-unrelated.events.json';
        file_put_contents($outside, '{}');
        try {
            $spool->delete($outside);
            self::assertFileExists($outside);
        } finally {
            unlink($outside);
        }
    }

    public function testLegacyUploadsAreDrainedInBoundedBatches(): void
    {
        $legacy = $this->uploadBaseDir . '/debugbundle-spool';
        mkdir($legacy, 0700, true);
        for ($index = 0; $index < 26; $index++) {
            file_put_contents($legacy . sprintf('/%03d.events.json', $index), json_encode([
                'events' => [['event_id' => 'evt-' . $index,
                    'service' => ['name' => 'wp', 'environment' => 'test'],
                    'payload' => ['message' => 'password=raw-legacy-canary'],
                ]],
            ], JSON_THROW_ON_ERROR));
        }

        $spool = new RelaySpool();
        $spool->migrateLegacy();

        self::assertCount(1, glob($legacy . '/*.events.json'));
        self::assertCount(25, $spool->files());
        $spool->migrateLegacy();
        self::assertSame([], glob($legacy . '/*.events.json'));
        self::assertCount(26, $spool->files());
        foreach ($spool->files() as $file) {
            self::assertStringNotContainsString('raw-legacy-canary', (string) file_get_contents($file));
        }
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
