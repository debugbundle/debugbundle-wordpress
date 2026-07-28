<?php

declare(strict_types=1);

namespace DebugBundleWp;

final class RelayForwardResult
{
    /**
     * @param list<array<string, mixed>>|null $retryEvents
     *        Null retains the original batch; an array replaces it with only
     *        the indexed retryable rejections.
     */
    public function __construct(
        public readonly bool $success,
        public readonly bool $drop,
        public readonly ?string $error,
        public readonly ?array $retryEvents = null,
    ) {
    }
}
