<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\Policy;

use Maatify\RateLimiter\Contract\BlockPolicyInterface;
use Maatify\RateLimiter\DTO\BudgetConfigDTO;
use Maatify\RateLimiter\DTO\ScoreDeltasDTO;
use Maatify\RateLimiter\DTO\ScoreThresholdsDTO;

class ApiHeavyProtectionPolicy implements BlockPolicyInterface
{
    private array $limits;

    public function __construct(array $limits = [])
    {
        $this->limits = array_merge([
            'k2' => 120, // Soft
            'k3' => 300, // Hard L2
            'k1' => 600, // Hard L3
        ], $limits);
    }

    public function getName(): string
    {
        return 'api_heavy_protection';
    }

    public function getScoreThresholds(): ScoreThresholdsDTO
    {
        return new ScoreThresholdsDTO([
            'k2' => [$this->limits['k2'] => 1],
            'k3' => [$this->limits['k3'] => 2],
            'k1' => [$this->limits['k1'] => 3],
        ]);
    }

    public function getScoreDeltas(): ScoreDeltasDTO
    {
        return new ScoreDeltasDTO(access: 1);
    }

    public function getFailureMode(): string
    {
        return 'FAIL_OPEN';
    }

    public function getBudgetConfig(): ?BudgetConfigDTO
    {
        return null;
    }
}
