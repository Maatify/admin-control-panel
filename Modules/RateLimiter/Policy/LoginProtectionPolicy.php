<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\Policy;

use Maatify\RateLimiter\Contract\BlockPolicyInterface;
use Maatify\RateLimiter\DTO\BudgetConfigDTO;
use Maatify\RateLimiter\DTO\PolicyThresholdsDTO;
use Maatify\RateLimiter\DTO\ScoreDeltasDTO;
use Maatify\RateLimiter\DTO\ScoreThresholdsDTO;

class LoginProtectionPolicy implements BlockPolicyInterface
{
    public function getName(): string
    {
        return 'login_protection';
    }

    public function getScoreThresholds(): PolicyThresholdsDTO
    {
        // Login uses K4 as primary signal
        return new PolicyThresholdsDTO(
            k4: new ScoreThresholdsDTO(5, 8, 12)
        );
    }

    public function getScoreDeltas(): ScoreDeltasDTO
    {
        return new ScoreDeltasDTO(
            k1_spray: 5,
            k2_missing_fp: 4,
            k4_failure: 3,
            k4_repeated_missing_fp: 6,
            k5_failure: 2
        );
    }

    public function getFailureMode(): string
    {
        return 'FAIL_CLOSED';
    }

    public function getBudgetConfig(): ?BudgetConfigDTO
    {
        return new BudgetConfigDTO(20, 3);
    }
}
