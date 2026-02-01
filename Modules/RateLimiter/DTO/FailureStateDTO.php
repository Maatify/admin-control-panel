<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\DTO;

class FailureStateDTO
{
    public const STATE_CLOSED = 'CLOSED';
    public const STATE_OPEN = 'OPEN';
    public const STATE_HALF_OPEN = 'HALF_OPEN';

    public function __construct(
        public readonly string $state,
        public readonly int $failureCount,
        public readonly int $lastFailureTimestamp,
        public readonly bool $isDegraded
    ) {}
}
