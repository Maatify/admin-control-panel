<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\Policy;

use Maatify\RateLimiter\Contract\BlockPolicyInterface;

class LoginProtectionPolicy implements BlockPolicyInterface
{
    public function getName(): string
    {
        return 'login_protection';
    }

    public function getScoreThresholds(): array
    {
        return [
            'k4' => [
                5 => 1, // Soft Block L1
                8 => 2, // Hard Block L2
                12 => 3, // Hard Block L3+
            ],
        ];
    }

    public function getScoreDeltas(): array
    {
        return [
            'k5_failure' => 2, // same known device
            'k4_failure' => 3, // new device
            'k2_missing_fp' => 4,
            'k4_repeated_missing_fp' => 6,
            'k1_spray' => 5,
        ];
    }

    public function getFailureMode(): string
    {
        return 'FAIL_CLOSED';
    }

    public function getBudgetConfig(): ?array
    {
        return [
            'threshold' => 20,
            'block_level' => 3,
            'epoch_seconds' => 86400,
        ];
    }
}
