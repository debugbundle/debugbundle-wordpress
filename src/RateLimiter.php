<?php

declare(strict_types=1);

namespace DebugBundleWp;

final class RateLimiter
{
    public function __construct(
        private readonly string $prefix = 'debugbundle_rl_',
        private readonly int $windowSeconds = 60,
        private readonly int $maxRequests = 60,
    ) {
    }

    public function allow(?string $ipAddress): bool
    {
        $key = $this->prefix . Sanitization::hashIp($ipAddress);
        $currentCount = 0;

        if (function_exists('get_transient')) {
            $value = \get_transient($key);
            $currentCount = is_numeric($value) ? (int) $value : 0;
        }

        if ($currentCount >= $this->maxRequests) {
            return false;
        }

        if (function_exists('set_transient')) {
            \set_transient($key, $currentCount + 1, $this->windowSeconds);
        }

        return true;
    }

    public function retryAfter(): int
    {
        return $this->windowSeconds;
    }
}
