<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\DTO;

class ScoreThresholdsDTO
{
    /**
     * @param int $l1 Soft Block Threshold
     * @param int $l2 Hard Block Threshold
     * @param int $l3 Hard Block Extended Threshold
     */
    public function __construct(
        public readonly int $l1,
        public readonly int $l2,
        public readonly int $l3
    ) {}
}
