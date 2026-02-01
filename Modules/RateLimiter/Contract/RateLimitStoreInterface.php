<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\Contract;

use Maatify\RateLimiter\DTO\Store\BlockStateDTO;
use Maatify\RateLimiter\DTO\Store\BudgetStateDTO;
use Maatify\RateLimiter\DTO\Store\RateLimitStateDTO;

interface RateLimitStoreInterface
{
    /**
     * Increment a counter atomically.
     * If the key does not exist, it is created with the given TTL.
     * If it exists, the TTL is NOT updated.
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
     * @return RateLimitStateDTO|null
     */
    public function get(string $key): ?RateLimitStateDTO;

    /**
     * Set a value (overwrite).
     *
     * @param string $key
     * @param int $value
     * @param int $ttlSeconds
     * @return void
     */
    public function set(string $key, int $value, int $ttlSeconds): void;

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
     * @return BlockStateDTO|null
     */
    public function checkBlock(string $key): ?BlockStateDTO;

    /**
     * Get budget status.
     *
     * @param string $key
     * @return BudgetStateDTO|null
     */
    public function getBudget(string $key): ?BudgetStateDTO;

    /**
     * Increment a budget counter.
     * Logic: If key empty, start epoch at now, count = amount.
     * If key exists, increment count.
     * Returns the current state.
     *
     * @param string $key
     * @param int $epochDurationSeconds
     * @param int $amount
     * @return BudgetStateDTO
     */
    public function incrementBudget(string $key, int $epochDurationSeconds, int $amount = 1): BudgetStateDTO;

    /**
     * Check backend health.
     *
     * @return bool
     */
    public function isHealthy(): bool;
}
