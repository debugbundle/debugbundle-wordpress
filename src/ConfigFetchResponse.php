<?php

declare(strict_types=1);

namespace DebugBundleWp;

final class ConfigFetchResponse
{
    /** @param array<string, string> $headers */
    public function __construct(
        public readonly int $statusCode,
        public readonly array $headers,
        private readonly mixed $payload,
    ) {
    }

    public function json(): mixed
    {
        return $this->payload;
    }
}
