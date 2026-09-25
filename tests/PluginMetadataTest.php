<?php

declare(strict_types=1);

namespace DebugBundleWp\Tests;

use PHPUnit\Framework\TestCase;

final class PluginMetadataTest extends TestCase
{
    public function testReleaseMetadataDeclaresWordPress71Compatibility(): void
    {
        $plugin = (string) file_get_contents(dirname(__DIR__) . '/debugbundle.php');
        $readme = (string) file_get_contents(dirname(__DIR__) . '/readme.txt');

        self::assertStringContainsString('Version:           2.0.1', $plugin);
        self::assertStringContainsString("define('DEBUGBUNDLE_WORDPRESS_VERSION', '2.0.1');", $plugin);
        self::assertStringContainsString("define('DEBUGBUNDLE_WORDPRESS_BROWSER_SDK_VERSION', '3.0.2');", $plugin);
        self::assertStringContainsString('Tested up to: 7.1', $readme);
        self::assertStringContainsString('Stable tag: 2.0.1', $readme);
    }

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

    public function testBothPublicationPathsEnforceProductionCoverage(): void
    {
        foreach (['release.yml', 'deploy-wordpress-org.yml'] as $workflowName) {
            $workflow = (string) file_get_contents(dirname(__DIR__) . '/.github/workflows/' . $workflowName);
            self::assertStringContainsString('coverage: xdebug', $workflow);
            self::assertStringContainsString('make coverage', $workflow);
            self::assertStringContainsString('pnpm build', $workflow);
        }
    }

    public function testPreviousVersionCannotBeAssembledFromCurrentSource(): void
    {
        $output = [];
        $status = 0;
        exec('sh ' . escapeshellarg(dirname(__DIR__) . '/scripts/assemble-release.sh') . ' 1.5.0 2>&1', $output, $status);

        self::assertNotSame(0, $status);
        self::assertStringContainsString('debugbundle.php version does not match 1.5.0', implode("\n", $output));
    }
}
