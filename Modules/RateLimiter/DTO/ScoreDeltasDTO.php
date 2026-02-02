<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\DTO;

class ScoreDeltasDTO
{
    public function __construct(
        public readonly int $access = 0,
        public readonly int $k1_spray = 0,
        public readonly int $k2_missing_fp = 0,
        public readonly int $k4_failure = 0,
        public readonly int $k4_repeated_missing_fp = 0,
        public readonly int $k5_failure = 0
    ) {}
}
