<?php

declare(strict_types=1);

namespace DebugBundleWp;

final class AdminTestResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
    ) {
    }
}