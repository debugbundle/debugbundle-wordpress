<?php

declare(strict_types=1);

namespace DebugBundleWp\Tests;

use DebugBundleWp\Settings;
use PHPUnit\Framework\TestCase;

final class SettingsTest extends TestCase
{
    public function testSanitizeClampsSampleRatesAndNormalizesFields(): void
    {
        $settings = new Settings();

        $sanitized = $settings->sanitize([
            'project_token' => ' dbundle_proj_123 ',
            'sample_rate' => 5,
            'browser_session_sample_rate' => -1,
            'browser_max_events_per_session' => 0,
            'log_level' => 'INVALID',
            'enabled' => true,
        ]);

        self::assertSame('dbundle_proj_123', $sanitized['project_token']);
        self::assertSame(1.0, $sanitized['sample_rate']);
        self::assertSame(0.0, $sanitized['browser_session_sample_rate']);
        self::assertSame(1, $sanitized['browser_max_events_per_session']);
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
