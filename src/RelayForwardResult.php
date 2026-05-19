<?php

declare(strict_types=1);

namespace DebugBundleWp;

final class RelayForwardResult
{
    public function __construct(
        public readonly bool $success,
        public readonly bool $drop,
        public readonly ?string $error,
    ) {
    }
}
