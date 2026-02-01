<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\Device;

use Maatify\RateLimiter\Contract\CorrelationStoreInterface;
use Maatify\RateLimiter\DTO\RateLimitContextDTO;

class EphemeralBucket
{
    private const MAX_DEVICES_PER_ACCOUNT = 10;
    private const MAX_DEVICES_PER_IP = 50;
    private const CAP_WINDOW = 3600; // 1 hour window for cap counting?
    // Docs say "Caps MUST be time-windowed".
    // "Ephemeral bucket MUST have TTL <= 30 minutes".

    public function __construct(
        private readonly CorrelationStoreInterface $store
    ) {}

    public function resolveKey(RateLimitContextDTO $context, string $fingerprintHash): string
    {
        // If no account ID, we only check IP cap?
        // But Ephemeral applies per (IP_PREFIX, AccountID).

        if ($context->accountId) {
            $key = "dev_cap:acc:{$context->accountId}";
            $count = $this->store->addDistinct($key, $fingerprintHash, self::CAP_WINDOW);
            if ($count > self::MAX_DEVICES_PER_ACCOUNT) {
                return $this->getEphemeralKey($context);
            }
        }

        $ipKey = "dev_cap:ip:{$context->ip}";
        $count = $this->store->addDistinct($ipKey, $fingerprintHash, self::CAP_WINDOW);
        if ($count > self::MAX_DEVICES_PER_IP) {
            return $this->getEphemeralKey($context);
        }

        return $fingerprintHash;
    }

    private function getEphemeralKey(RateLimitContextDTO $context): string
    {
        // Ephemeral key scoped to IP and Account (if present)
        // "Ephemeral applies per (IP_PREFIX, AccountID) context"
        $scope = $context->accountId ? "acc:{$context->accountId}" : "ip:{$context->ip}";
        return "ephemeral:{$scope}";
    }
}
