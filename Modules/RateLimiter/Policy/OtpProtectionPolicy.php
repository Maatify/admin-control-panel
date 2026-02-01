<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\Policy;

use Maatify\RateLimiter\Contract\BlockPolicyInterface;

class OtpProtectionPolicy implements BlockPolicyInterface
{
    public function getName(): string
    {
        return 'otp_protection';
    }

    public function getScoreThresholds(): array
    {
        return [
            'k4' => [
                4 => 1, // Soft L1
                7 => 2, // Hard L2
                10 => 3, // Hard L3+
            ],
        ];
    }

    public function getScoreDeltas(): array
    {
        return [
            'k5_failure' => 4,
            'k4_failure' => 5,
            'k2_missing_fp' => 6,
            'k4_repeated_missing_fp' => 8,
        ];
    }

    public function getFailureMode(): string
    {
        return 'FAIL_CLOSED';
    }

    public function getBudgetConfig(): ?array
    {
        return [
            'threshold' => 10,
            'block_level' => 4,
            'epoch_seconds' => 86400,
        ];
    }
}
