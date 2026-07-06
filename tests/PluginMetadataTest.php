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
        self::assertStringContainsString('Turn WordPress PHP errors and browser exceptions into DebugBundle reports for developers and AI agents.', $readme);
        self::assertStringContainsString('DebugBundle is built for agent-first debugging workflows.', $readme);
        self::assertStringContainsString('== External services ==', $readme);
        self::assertStringContainsString('https://debugbundle.com/terms', $readme);
        self::assertStringContainsString('https://debugbundle.com/privacy', $readme);
        self::assertStringContainsString('Search for DebugBundle, then install and activate the plugin.', $readme);
    }
}
