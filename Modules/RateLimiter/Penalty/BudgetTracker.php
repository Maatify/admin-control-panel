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

    public function increment(string $key): void
    {
        $this->store->incrementBudget($key, self::EPOCH_DURATION);
    }

    public function getStatus(string $key): array
    {
        $dto = $this->store->getBudget($key);
        return $dto ? ['count' => $dto->count, 'epoch_start' => $dto->epochStart] : ['count' => 0, 'epoch_start' => 0];
    }

    public function isExceeded(string $key, int $limit): bool
    {
        $status = $this->store->getBudget($key);

        if ($status && $status->count >= $limit) {
            $now = time();
            if ($status->epochStart + self::EPOCH_DURATION > $now) {
                return true;
            }
        }
        return false;
    }
}
