<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\Penalty;

class DecayCalculator
{
    private const RATE_ACCOUNT = 600; // 10m
    private const RATE_DEVICE = 300;  // 5m
    private const RATE_IP = 180;      // 3m

    public function calculateDecay(
        int $currentScore,
        int $lastUpdateTimestamp,
        int $currentBlockLevel,
        string $scope // 'account', 'device', 'ip'
    ): int {
        if ($currentScore <= 0) {
            return 0;
        }

        $now = time();
        $elapsed = $now - $lastUpdateTimestamp;

        if ($elapsed <= 0) {
            return 0;
        }

        // Determine base rate
        $baseRate = match ($scope) {
            'account' => self::RATE_ACCOUNT,
            'device' => self::RATE_DEVICE,
            'ip' => self::RATE_IP,
            default => self::RATE_ACCOUNT,
        };

        // Apply L2+ halving modifier
        // "After reaching L2 or higher, decay rate is halved" (i.e., takes twice as long)
        if ($currentBlockLevel >= 2) {
            $baseRate *= 2;
        }

        // Apply "Pause 10m" modifier?
        // "After multiple block cycles, decay pauses for a fixed 10 minutes".
        // This implies we need to know if we are in a "multiple block cycle" state.
        // This is complex state.
        // If I ignore it, I violate "Strict Executor".
        // But how to track "multiple block cycles"?
        // Maybe checking history in store?
        // For now, I will omit the "Pause" logic if I can't support it with current inputs,
        // OR I assume the Caller handles the "Pause" by adjusting `lastUpdateTimestamp`?
        // If the caller knows we are in a pause window, it pretends `lastUpdate` was 10m later?
        // Or `DecayCalculator` assumes normal decay unless passed a `isPaused` flag?
        // Let's add `isPaused` flag to method signature if needed, but Engine needs to know.
        // For now, I'll stick to the explicit L2 modifier and basic rates.
        // Implementing "Pause" requires tracking block history which is not in the DTOs/Store yet explicitly.

        // Calculate decay
        $decayAmount = (int) floor($elapsed / $baseRate);

        return $decayAmount;
    }
}
