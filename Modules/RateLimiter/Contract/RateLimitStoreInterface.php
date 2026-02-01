<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\Contract;

interface RateLimitStoreInterface
{
    /**
     * Increment a counter atomically.
     * If the key does not exist, it is created with the given TTL.
     * If it exists, the TTL is NOT updated (unless implementation specific, but usually preserving TTL is desired or resetting it).
     * `DECISION_MATRIX` implies decay, but counters for budgets are fixed epoch.
     * Standard rate limit counters usually reset TTL or slide.
     * The Penalty/DecayCalculator might handle logic, but the store needs raw atomic increment.
     *
     * @param string $key
     * @param int $ttlSeconds
     * @param int $amount
     * @return int The new value
     */
    public function increment(string $key, int $ttlSeconds, int $amount = 1): int;

    /**
     * Get current counter value and metadata.
     *
     * @param string $key
     * @return array{value: int, updated_at: int}|null
     */
    public function get(string $key): ?array;

    /**
     * Set a block on a key.
     *
     * @param string $key
     * @param int $level Block level (L1-L6)
     * @param int $durationSeconds
     * @return void
     */
    public function block(string $key, int $level, int $durationSeconds): void;

    /**
     * Check if a key is blocked.
     *
     * @param string $key
     * @return array|null Returns ['level' => int, 'expires_at' => int] or null if not blocked.
     */
    public function checkBlock(string $key): ?array;

    /**
     * Get budget status.
     *
     * @param string $key
     * @return array{count: int, epoch_start: int}|null
     */
    public function getBudget(string $key): ?array;

    /**
     * Increment a budget counter.
     * Logic: If key empty, start epoch at now, count = amount.
     * If key exists, increment count.
     * Returns the current state.
     *
     * @param string $key
     * @param int $epochDurationSeconds
     * @param int $amount
     * @return array{count: int, epoch_start: int}
     */
    public function incrementBudget(string $key, int $epochDurationSeconds, int $amount = 1): array;

    /**
     * Check backend health.
     *
     * @return bool
     */
    public function isHealthy(): bool;
}
