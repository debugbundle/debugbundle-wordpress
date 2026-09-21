<?php

declare(strict_types=1);

namespace DebugBundleWp\Tests;

use DebugBundleWp\Compatibility;
use DebugBundleWp\Plugin;
use DebugBundleWp\Settings;
use PHPUnit\Framework\TestCase;

final class PluginLifecycleAndCompatibilityTest extends TestCase
{
    private string $uploadBaseDir;
    private string $pluginFile;

    protected function setUp(): void
    {
        $this->uploadBaseDir = sys_get_temp_dir() . '/debugbundle-plugin-lifecycle-' . bin2hex(random_bytes(6));
        $this->pluginFile = $this->uploadBaseDir . '/debugbundle.php';
        mkdir($this->uploadBaseDir, 0777, true);
        file_put_contents($this->pluginFile, "<?php\n");
        $GLOBALS['debugbundle_wp_test_upload_basedir'] = $this->uploadBaseDir;
        $GLOBALS['debugbundle_wp_test_wordpress_version'] = '6.8';
        $GLOBALS['debugbundle_wp_test_options'] = [
            Settings::OPTION_NAME => [
                'enabled' => true,
                'project_token' => 'dbundle_proj_test',
                'backend_capture_enabled' => false,
                'frontend_capture_enabled' => true,
            ],
        ];
        foreach ([
            'debugbundle_wp_test_actions',
            'debugbundle_wp_test_activation_hooks',
            'debugbundle_wp_test_deactivation_hooks',
            'debugbundle_wp_test_cleared_hooks',
        ] as $key) {
            $GLOBALS[$key] = [];
        }
        $this->resetPluginSingleton();
    }

    protected function tearDown(): void
    {
        $this->resetPluginSingleton();
        $this->removeDirectory((new \DebugBundleWp\RelaySpool())->path());
        unset(
            $GLOBALS['debugbundle_wp_test_upload_basedir'],
            $GLOBALS['debugbundle_wp_test_wordpress_version'],
            $GLOBALS['debugbundle_wp_test_options']
        );
        $this->removeDirectory($this->uploadBaseDir);
    }

    public function testCompatibilityChecksExplicitAndDetectedRuntimeVersions(): void
    {
        self::assertTrue(Compatibility::isPhpSupported('8.2.0'));
        self::assertFalse(Compatibility::isPhpSupported('8.1.99'));
        self::assertTrue(Compatibility::isWordPressSupported('6.5'));
        self::assertFalse(Compatibility::isWordPressSupported('6.4.9'));
        self::assertTrue(Compatibility::isWordPressSupported());
        self::assertTrue(Compatibility::isRuntimeSupported());
        self::assertSame(
            'DebugBundle requires WordPress 6.5 and PHP 8.2 or newer.',
            Compatibility::failureMessage()
        );
    }

    public function testPluginBootRegistersLifecycleAndRuntimeHooksOnce(): void
    {
        Plugin::boot($this->pluginFile);
        Plugin::boot($this->pluginFile);

        self::assertArrayHasKey($this->pluginFile, $GLOBALS['debugbundle_wp_test_activation_hooks']);
        self::assertArrayHasKey($this->pluginFile, $GLOBALS['debugbundle_wp_test_deactivation_hooks']);
        self::assertCount(1, $GLOBALS['debugbundle_wp_test_actions']['plugins_loaded'][10] ?? []);
        self::assertCount(1, $GLOBALS['debugbundle_wp_test_actions']['debugbundle_flush_relay_spool'][10] ?? []);

        $instance = (new \ReflectionClass(Plugin::class))->getStaticPropertyValue('instance');
        self::assertInstanceOf(Plugin::class, $instance);
        $instance->activate();
        self::assertDirectoryExists((new \DebugBundleWp\RelaySpool())->path());
        self::assertStringNotContainsString($this->uploadBaseDir, (new \DebugBundleWp\RelaySpool())->path());

        $instance->onPluginsLoaded();
        self::assertNotEmpty($GLOBALS['debugbundle_wp_test_actions']['admin_menu'][10] ?? []);
        self::assertNotEmpty($GLOBALS['debugbundle_wp_test_actions']['wp_enqueue_scripts'][10] ?? []);
        self::assertNotEmpty($GLOBALS['debugbundle_wp_test_actions']['rest_api_init'][10] ?? []);

        $instance->deactivate();
        self::assertContains('debugbundle_flush_relay_spool', $GLOBALS['debugbundle_wp_test_cleared_hooks']);
    }

    public function testActivationFailsClosedOnUnsupportedWordPress(): void
    {
        Plugin::boot($this->pluginFile);
        $instance = (new \ReflectionClass(Plugin::class))->getStaticPropertyValue('instance');
        $GLOBALS['debugbundle_wp_test_wordpress_version'] = '6.4';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('DebugBundle requires WordPress 6.5 and PHP 8.2 or newer.');
        $instance->activate();
    }

    private function resetPluginSingleton(): void
    {
        $property = new \ReflectionProperty(Plugin::class, 'instance');
        $property->setValue(null, null);
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
