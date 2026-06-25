<?php

declare(strict_types=1);

namespace DebugBundleWp\Tests;

use PHPUnit\Framework\TestCase;

final class PluginMetadataTest extends TestCase
{
    public function testReadmeDeclaresSubmitterAndExternalServiceDisclosure(): void
    {
        $readme = (string) file_get_contents(dirname(__DIR__) . '/readme.txt');

        self::assertStringContainsString('Contributors: owenfar, debugbundle', $readme);
        self::assertStringContainsString('== External services ==', $readme);
        self::assertStringContainsString('https://debugbundle.com/terms', $readme);
        self::assertStringContainsString('https://debugbundle.com/privacy', $readme);
    }
}
