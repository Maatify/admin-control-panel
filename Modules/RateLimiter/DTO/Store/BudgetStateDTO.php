<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\DTO\Store;

class BudgetStateDTO
{
    public function __construct(
        public readonly int $count,
        public readonly int $epochStart
    ) {}
}
