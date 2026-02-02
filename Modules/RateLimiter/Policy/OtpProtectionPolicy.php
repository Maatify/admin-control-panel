<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\Policy;

use Maatify\RateLimiter\Contract\BlockPolicyInterface;
use Maatify\RateLimiter\DTO\BudgetConfigDTO;
use Maatify\RateLimiter\DTO\ScoreDeltasDTO;
use Maatify\RateLimiter\DTO\ScoreThresholdsDTO;

class OtpProtectionPolicy implements BlockPolicyInterface
{
    public function getName(): string
    {
        return 'otp_protection';
    }

    public function getScoreThresholds(): ScoreThresholdsDTO
    {
        return new ScoreThresholdsDTO([
            'k4' => [
                4 => 1,
                7 => 2,
                10 => 3,
            ],
        ]);
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
