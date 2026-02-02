<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\Penalty;

use Maatify\RateLimiter\Contract\RateLimitStoreInterface;
use Maatify\RateLimiter\DTO\BudgetStatusDTO;

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

    public function getStatus(string $key): BudgetStatusDTO
    {
        $dto = $this->store->getBudget($key);
        if ($dto) {
            return new BudgetStatusDTO($dto->count, $dto->epochStart);
        }
        return new BudgetStatusDTO(0, 0);
    }

    public function isExceeded(string $key, int $limit): bool
    {
        $status = $this->getStatus($key);

        if ($status->count >= $limit) {
            $now = time();
            if ($status->epochStart + self::EPOCH_DURATION > $now) {
                return true;
            }
        }
        return false;
    }
}
