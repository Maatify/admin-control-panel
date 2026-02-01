<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\Contract;

interface BlockPolicyInterface
{
    public function getName(): string;

    /**
     * Get score thresholds mapping score to block level.
     * e.g. [5 => 1, 8 => 2, 12 => 3]
     * or keyed by scope: ['k4' => [...], 'k1' => [...]]
     * @return array
     */
    public function getScoreThresholds(): array;

    /**
     * Get score deltas for scenarios.
     * @return array<string, int>
     */
    public function getScoreDeltas(): array;

    /**
     * Get failure mode (FAIL_CLOSED, FAIL_OPEN).
     * @return string
     */
    public function getFailureMode(): string;

    /**
     * Get budget configuration.
     * @return array{threshold: int, block_level: int, epoch_seconds: int}|null
     */
    public function getBudgetConfig(): ?array;
}
