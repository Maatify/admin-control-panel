<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\Penalty;

class PenaltyLadder
{
    public const L1_DURATION = 15;
    public const L2_DURATION = 60;
    public const L3_DURATION = 300;
    public const L4_DURATION = 1800;
    public const L5_DURATION = 21600;
    public const L6_DURATION = 86400;

    public static function getDuration(int $level): int
    {
        return match ($level) {
            1 => self::L1_DURATION,
            2 => self::L2_DURATION,
            3 => self::L3_DURATION,
            4 => self::L4_DURATION,
            5 => self::L5_DURATION,
            6 => self::L6_DURATION,
            default => self::L6_DURATION,
        };
    }
}
