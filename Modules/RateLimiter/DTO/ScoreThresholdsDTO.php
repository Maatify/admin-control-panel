<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\DTO;

class ScoreThresholdsDTO
{
    /**
     * @param array<string, int> $thresholds key (L2/L3) => score
     */
    public function __construct(
        public readonly array $thresholds
    ) {}
}
