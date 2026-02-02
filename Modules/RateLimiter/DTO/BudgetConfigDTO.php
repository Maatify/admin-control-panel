<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\DTO;

class BudgetConfigDTO
{
    public function __construct(
        public readonly int $threshold,
        public readonly int $block_level
    ) {}
}
