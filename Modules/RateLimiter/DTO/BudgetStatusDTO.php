<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\DTO;

class BudgetStatusDTO
{
    public function __construct(
        public readonly int $count,
        public readonly int $epochStart
    ) {}
}
