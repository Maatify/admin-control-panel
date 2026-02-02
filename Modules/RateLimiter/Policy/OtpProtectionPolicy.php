<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\Policy;

use Maatify\RateLimiter\Contract\BlockPolicyInterface;
use Maatify\RateLimiter\DTO\BudgetConfigDTO;
use Maatify\RateLimiter\DTO\PolicyThresholdsDTO;
use Maatify\RateLimiter\DTO\ScoreDeltasDTO;
use Maatify\RateLimiter\DTO\ScoreThresholdsDTO;

class OtpProtectionPolicy implements BlockPolicyInterface
{
    public function getName(): string
    {
        return 'otp_protection';
    }

    public function getScoreThresholds(): PolicyThresholdsDTO
    {
        return new PolicyThresholdsDTO(
            k4: new ScoreThresholdsDTO(4, 7, 10)
        );
    }

    public function getScoreDeltas(): ScoreDeltasDTO
    {
        return new ScoreDeltasDTO(
            k2_missing_fp: 6,
            k4_failure: 5,
            k4_repeated_missing_fp: 8,
            k5_failure: 4
        );
    }

    public function getFailureMode(): string
    {
        return 'FAIL_CLOSED';
    }

    public function getBudgetConfig(): ?BudgetConfigDTO
    {
        return new BudgetConfigDTO(10, 4);
    }
}
