<?php

declare(strict_types=1);

namespace {

    if (!function_exists('get_option')) {
        function get_option(string $option, mixed $default = false): mixed
        {
            return $GLOBALS['debugbundle_wp_test_options'][$option] ?? $default;
        }
    }

    if (!function_exists('plugins_url')) {
        function plugins_url(string $path, string $pluginFile): string
        {
            return 'https://example.com/wp-content/plugins/debugbundle/' . ltrim($path, '/');
        }
    }

    if (!function_exists('wp_register_script')) {
        function wp_register_script(string $handle, string $src, array $deps, string $version, bool $inFooter): void
        {
            $GLOBALS['debugbundle_wp_register_script_calls'][] = [$handle, $src, $deps, $version, $inFooter];
        }
    }

    if (!function_exists('wp_add_inline_script')) {
        function wp_add_inline_script(string $handle, string $script, string $position): void
        {
            $GLOBALS['debugbundle_wp_inline_script_calls'][] = [$handle, $script, $position];
        }
    }

    if (!function_exists('wp_enqueue_script')) {
        function wp_enqueue_script(string $handle): void
        {
            $GLOBALS['debugbundle_wp_enqueue_script_calls'][] = [$handle];
        }
    }

    if (!function_exists('wp_json_encode')) {
        function wp_json_encode(mixed $value): string|false
        {
            return json_encode($value);
        }
    }
}

namespace DebugBundleWp\Tests {

use DebugBundleWp\BrowserAssets;
use DebugBundleWp\Settings;
use PHPUnit\Framework\TestCase;

final class BrowserAssetsTest extends TestCase
{
    private string $pluginDir;
    private string $pluginFile;

    protected function setUp(): void
    {
        $this->pluginDir = sys_get_temp_dir() . '/debugbundle-browser-assets-' . bin2hex(random_bytes(6));
        $this->pluginFile = $this->pluginDir . '/debugbundle.php';
        mkdir($this->pluginDir . '/assets/dist', 0777, true);
        file_put_contents($this->pluginFile, "<?php\n");
        file_put_contents($this->pluginDir . '/assets/dist/debugbundle-browser.js', 'console.log("debugbundle");');

        $GLOBALS['debugbundle_wp_test_options'] = [
            Settings::OPTION_NAME => [
                'enabled' => true,
                'project_token' => 'dbundle_proj_test_1234567890',
                'environment' => 'production',
                'service' => 'demo-wordpress',
                'endpoint' => Settings::DEFAULT_ENDPOINT,
                'backend_capture_enabled' => true,
                'frontend_capture_enabled' => true,
                'browser_load_in_head' => false,
                'sample_rate' => 1.0,
                'browser_session_sample_rate' => 1.0,
                'browser_max_events_per_session' => 100,
                'browser_capture_console' => false,
                'log_level' => 'warning',
                'delete_on_uninstall' => false,
                'settings_version' => 1,
            ],
        ];
        $GLOBALS['debugbundle_wp_register_script_calls'] = [];
        $GLOBALS['debugbundle_wp_inline_script_calls'] = [];
        $GLOBALS['debugbundle_wp_enqueue_script_calls'] = [];
    }

    protected function tearDown(): void
    {
        @unlink($this->pluginDir . '/assets/dist/debugbundle-browser.js');
        @rmdir($this->pluginDir . '/assets/dist');
        @rmdir($this->pluginDir . '/assets');
        @unlink($this->pluginFile);
        @rmdir($this->pluginDir);
    }

    public function testEnqueueLoadsBrowserScriptInFooterByDefault(): void
    {
        $assets = new BrowserAssets(new Settings(), $this->pluginFile);
        $assets->enqueue();

        self::assertSame('debugbundle-browser', $GLOBALS['debugbundle_wp_register_script_calls'][0][0] ?? null);
        self::assertTrue($GLOBALS['debugbundle_wp_register_script_calls'][0][4] ?? false);
    }

    public function testEnqueueKeepsLegacySavedSettingsInFooterWhenFlagIsMissing(): void
    {
        unset($GLOBALS['debugbundle_wp_test_options'][Settings::OPTION_NAME]['browser_load_in_head']);

        $assets = new BrowserAssets(new Settings(), $this->pluginFile);
        $assets->enqueue();

        self::assertTrue($GLOBALS['debugbundle_wp_register_script_calls'][0][4] ?? false);
    }

    public function testEnqueueCanMoveBrowserScriptIntoHeadWhenEnabled(): void
    {
        $GLOBALS['debugbundle_wp_test_options'][Settings::OPTION_NAME]['browser_load_in_head'] = true;

        $assets = new BrowserAssets(new Settings(), $this->pluginFile);
        $assets->enqueue();

        self::assertFalse($GLOBALS['debugbundle_wp_register_script_calls'][0][4] ?? true);
        self::assertSame('before', $GLOBALS['debugbundle_wp_inline_script_calls'][0][2] ?? null);
        self::assertSame('debugbundle-browser', $GLOBALS['debugbundle_wp_enqueue_script_calls'][0][0] ?? null);
    }
}
}
