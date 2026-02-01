<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\DTO\Store;

class RateLimitStateDTO
{
    public function __construct(
        public readonly int $value,
        public readonly int $updatedAt
    ) {}
}
