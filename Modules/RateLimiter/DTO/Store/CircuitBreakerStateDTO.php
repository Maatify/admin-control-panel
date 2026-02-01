<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\DTO\Store;

class CircuitBreakerStateDTO
{
    public function __construct(
        public readonly string $status,
        public readonly array $failures,
        public readonly int $lastFailure,
        public readonly int $openSince,
        public readonly int $lastSuccess,
        public readonly array $reEntries
    ) {}
}
