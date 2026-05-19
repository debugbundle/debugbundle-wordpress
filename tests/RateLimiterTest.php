<?php

declare(strict_types=1);

namespace {
    if (!function_exists('get_transient')) {
        function get_transient(string $key): int|false
        {
            return \DebugBundleWp\Tests\RateLimiterTestState::$instance?->get($key) ?? false;
        }
    }

    if (!function_exists('set_transient')) {
        function set_transient(string $key, mixed $value, int $expiration): bool
        {
            \DebugBundleWp\Tests\RateLimiterTestState::$instance?->set($key, (int) $value);
            return true;
        }
    }
}

namespace DebugBundleWp\Tests {
    use DebugBundleWp\RateLimiter;
    use PHPUnit\Framework\TestCase;

    final class RateLimiterTest extends TestCase
    {
        protected function setUp(): void
        {
            RateLimiterTestState::$instance = new RateLimiterTestState();
        }

        public function testAllowStopsAfterConfiguredThreshold(): void
        {
            $limiter = new RateLimiter('test_', 60, 2);

            self::assertTrue($limiter->allow('127.0.0.1'));
            self::assertTrue($limiter->allow('127.0.0.1'));
            self::assertFalse($limiter->allow('127.0.0.1'));
        }
    }

    final class RateLimiterTestState
    {
        public static ?self $instance = null;

        /** @var array<string, int> */
        private array $values = [];

        public function get(string $key): int|false
        {
            return $this->values[$key] ?? false;
        }

        public function set(string $key, int $value): void
        {
            $this->values[$key] = $value;
        }
    }
}
