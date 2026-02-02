<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\DTO;

use Maatify\RateLimiter\DTO\ScoreThresholdsDTO;

class PolicyThresholdsDTO
{
    public function __construct(
        public readonly ?ScoreThresholdsDTO $k1 = null,
        public readonly ?ScoreThresholdsDTO $k2 = null,
        public readonly ?ScoreThresholdsDTO $k3 = null,
        public readonly ?ScoreThresholdsDTO $k4 = null,
        public readonly ?ScoreThresholdsDTO $k5 = null,
        public readonly ?ScoreThresholdsDTO $default = null
    ) {}
}
