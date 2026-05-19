<?php

declare(strict_types=1);

namespace DebugBundleWp;

final class Compatibility
{
    public const MINIMUM_PHP_VERSION = '8.2';
    public const MINIMUM_WORDPRESS_VERSION = '6.5';

    public static function isRuntimeSupported(): bool
    {
        return self::isPhpSupported() && self::isWordPressSupported();
    }

    public static function isPhpSupported(?string $version = null): bool
    {
        return version_compare($version ?? PHP_VERSION, self::MINIMUM_PHP_VERSION, '>=');
    }

    public static function isWordPressSupported(?string $version = null): bool
    {
        $resolvedVersion = $version;
        if ($resolvedVersion === null && function_exists('get_bloginfo')) {
            $candidate = \get_bloginfo('version');
            $resolvedVersion = is_string($candidate) ? $candidate : null;
        }

        return $resolvedVersion === null || version_compare($resolvedVersion, self::MINIMUM_WORDPRESS_VERSION, '>=');
    }

    public static function failureMessage(): string
    {
        return sprintf(
            'DebugBundle requires WordPress %s and PHP %s or newer.',
            self::MINIMUM_WORDPRESS_VERSION,
            self::MINIMUM_PHP_VERSION,
        );
    }
}
