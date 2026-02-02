<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\Policy;

use Maatify\RateLimiter\Contract\BlockPolicyInterface;
use Maatify\RateLimiter\DTO\BudgetConfigDTO;
use Maatify\RateLimiter\DTO\ScoreDeltasDTO;
use Maatify\RateLimiter\DTO\ScoreThresholdsDTO;

class LoginProtectionPolicy implements BlockPolicyInterface
{
    public function getName(): string
    {
        return 'login_protection';
    }

    public function getScoreThresholds(): ScoreThresholdsDTO
    {
        // For Login: K4 thresholds are primary.
        // We put them in a structure evaluation pipeline understands.
        // We'll standardize key-based thresholds in the DTO if needed,
        // but simple array property in DTO is fine as long as strict typed.
        return new ScoreThresholdsDTO([
            'k4' => [
                5 => 1,
                8 => 2,
                12 => 3,
            ],
        ]);
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
