<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\Penalty;

use Maatify\RateLimiter\Contract\RateLimitStoreInterface;

class BudgetTracker
{
    private const EPOCH_DURATION = 86400; // 24h

    public function __construct(
        private readonly RateLimitStoreInterface $store
    ) {}

    public function increment(string $key): array
    {
        return $this->store->incrementBudget($key, self::EPOCH_DURATION);
    }

    public function getStatus(string $key): array
    {
        return $this->store->getBudget($key) ?? ['count' => 0, 'epoch_start' => 0];
    }

    public function isExceeded(string $key, int $limit): bool
    {
        $status = $this->getStatus($key);
        // Check if epoch is active and expired?
        // If epoch expired, the Store should have cleared it or we check time.
        // But store implementation might be lazy.
        // "Additional failures MUST NOT extend the epoch end time."
        // If the store returns an old epoch, we should probably treat it as new?
        // But standard RateLimitStore behavior for budget should handle TTL?
        // Let's assume Store handles TTL or we check `epoch_start`.

        if ($status['count'] >= $limit) {
            $now = time();
            if ($status['epoch_start'] + self::EPOCH_DURATION > $now) {
                return true;
            }
        }
        return false;
    }
}
