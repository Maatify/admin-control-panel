<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\DTO\Store;

class CircuitBreakerStateDTO
{
    /**
     * @param string $status
     * @param array<int, int> $failures
     * @param int $lastFailure
     * @param int $openSince
     * @param int $lastSuccess
     * @param array<int, int> $reEntries
     * @param int $failClosedUntil
     */
    public function __construct(
        public readonly string $status,
        public readonly array $failures,
        public readonly int $lastFailure,
        public readonly int $openSince,
        public readonly int $lastSuccess,
        public readonly array $reEntries,
        public readonly int $failClosedUntil = 0
    ) {}
}
