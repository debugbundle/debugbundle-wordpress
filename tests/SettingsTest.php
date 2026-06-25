<?php

declare(strict_types=1);

namespace DebugBundleWp\Tests;

use DebugBundleWp\Settings;
use PHPUnit\Framework\TestCase;

if (!function_exists('get_option')) {
    function get_option(string $option, mixed $default = false): mixed
    {
        return $GLOBALS['debugbundle_wp_test_options'][$option] ?? $default;
    }
}

final class SettingsTest extends TestCase
{
    protected function setUp(): void
    {
        unset($GLOBALS['debugbundle_wp_test_options']);
    }

    public function testSanitizeClampsSampleRatesAndNormalizesFields(): void
    {
        $settings = new Settings();

        $sanitized = $settings->sanitize([
            'project_token' => ' <strong>dbundle_proj_123</strong> ',
            'endpoint' => " https://api.debugbundle.com/v1/events\n",
            'environment' => '<em>Production</em>',
            'service' => ' checkout <script>alert(1)</script> ',
            'sample_rate' => 5,
            'browser_session_sample_rate' => -1,
            'browser_max_events_per_session' => 0,
            'browser_load_in_head' => true,
            'log_level' => 'INVALID',
            'enabled' => true,
        ]);

        self::assertSame('dbundle_proj_123', $sanitized['project_token']);
        self::assertSame('https://api.debugbundle.com/v1/events', $sanitized['endpoint']);
        self::assertSame('Production', $sanitized['environment']);
        self::assertSame('checkout alert(1)', $sanitized['service']);
        self::assertSame(1.0, $sanitized['sample_rate']);
        self::assertSame(0.0, $sanitized['browser_session_sample_rate']);
        self::assertSame(1, $sanitized['browser_max_events_per_session']);
        self::assertTrue($sanitized['browser_load_in_head']);
        self::assertSame('warning', $sanitized['log_level']);
    }

    public function testMaskProjectTokenDoesNotExposeMiddle(): void
    {
        $settings = new Settings();
        $sanitized = $settings->sanitize([
            'project_token' => 'dbundle_proj_abcdefghijklmnopqrstuvwxyz',
        ]);
        $masked = self::maskTokenFromArray($settings, $sanitized);

        self::assertStringStartsWith('dbundl', $masked);
        self::assertStringEndsWith('wxyz', $masked);
        self::assertStringNotContainsString('mnopqrst', $masked);
    }

    public function testNewInstallsDefaultToHeadLoading(): void
    {
        $settings = new Settings();

        self::assertTrue($settings->shouldLoadBrowserInHead());
    }

    public function testLegacySavedSettingsKeepFooterLoadingUntilExplicitlyChanged(): void
    {
        $GLOBALS['debugbundle_wp_test_options'] = [
            Settings::OPTION_NAME => [
                'enabled' => true,
                'project_token' => 'dbundle_proj_test_1234567890',
                'frontend_capture_enabled' => true,
                'settings_version' => 1,
            ],
        ];

        $settings = new Settings();

        self::assertFalse($settings->shouldLoadBrowserInHead());
    }

    /** @param array<string, mixed> $values */
    private static function maskTokenFromArray(Settings $settings, array $values): string
    {
        $projectToken = (string) ($values['project_token'] ?? '');
        if ($projectToken === '') {
            return '';
        }

        if (strlen($projectToken) <= 8) {
            return str_repeat('*', strlen($projectToken));
        }

        return substr($projectToken, 0, 6) . str_repeat('*', max(4, strlen($projectToken) - 10)) . substr($projectToken, -4);
    }
}
