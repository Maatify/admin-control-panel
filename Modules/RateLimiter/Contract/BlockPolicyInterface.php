<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\Contract;

use Maatify\RateLimiter\DTO\BudgetConfigDTO;
use Maatify\RateLimiter\DTO\PolicyThresholdsDTO;
use Maatify\RateLimiter\DTO\ScoreDeltasDTO;

interface BlockPolicyInterface
{
    public function getName(): string;

    /**
     * Get score thresholds mapping score to block level.
     */
    public function getScoreThresholds(): PolicyThresholdsDTO;

    /**
     * Get score deltas for scenarios.
     */
    public function getScoreDeltas(): ScoreDeltasDTO;

    /**
     * Get failure mode (FAIL_CLOSED, FAIL_OPEN).
     */
    public function getFailureMode(): string;

    /**
     * Get budget configuration.
     */
    public function getBudgetConfig(): ?BudgetConfigDTO;
}
