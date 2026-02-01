<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\Penalty;

use Maatify\RateLimiter\Contract\CorrelationStoreInterface;

class AntiEquilibriumGate
{
    private const WINDOW = 21600; // 6h
    private const THRESHOLD = 3;

    public function __construct(
        private readonly CorrelationStoreInterface $store
    ) {}

    public function recordSoftBlock(string $accountId): void
    {
        $key = "gate:soft:{$accountId}";
        $this->store->incrementWatchFlag($key, self::WINDOW);
    }

    public function shouldEscalate(string $accountId): bool
    {
        $key = "gate:soft:{$accountId}";
        return $this->store->getWatchFlag($key) >= self::THRESHOLD;
    }
}
