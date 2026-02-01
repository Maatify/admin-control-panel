<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\Contract;

interface CorrelationStoreInterface
{
    /**
     * Add an item to a set and return the cardinality.
     *
     * @param string $key
     * @param string $item
     * @param int $ttlSeconds
     * @return int Current distinct count
     */
    public function addDistinct(string $key, string $item, int $ttlSeconds): int;

    /**
     * Increment a watch flag counter.
     * Used for Anti-N-1 gaming.
     *
     * @param string $key
     * @param int $ttlSeconds
     * @return int Current value of the flag
     */
    public function incrementWatchFlag(string $key, int $ttlSeconds): int;

    /**
     * Get the value of a watch flag.
     *
     * @param string $key
     * @return int
     */
    public function getWatchFlag(string $key): int;
}
