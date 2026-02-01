<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\Device;

use Maatify\RateLimiter\Contract\CorrelationStoreInterface;
use Maatify\RateLimiter\DTO\RateLimitContextDTO;

class EphemeralBucket
{
    private const MAX_DEVICES_PER_ACCOUNT = 10;
    private const MAX_DEVICES_PER_IP = 50;
    private const CAP_WINDOW = 1800; // <= 30 mins

    public function __construct(
        private readonly CorrelationStoreInterface $store
    ) {}

    public function resolveKey(RateLimitContextDTO $context, string $realFingerprintHash): string
    {
        // Scope Key Calculation
        // Must use IP Prefix for IPv6 to prevent Key Explosion
        $ipScope = $this->getIpPrefix($context->ip);

        $scopeKey = $context->accountId
            ? "dev_cap:acc:{$context->accountId}"
            : "dev_cap:ip:{$ipScope}";

        $count = $this->store->addDistinct($scopeKey, $realFingerprintHash, self::CAP_WINDOW);

        $limit = $context->accountId ? self::MAX_DEVICES_PER_ACCOUNT : self::MAX_DEVICES_PER_IP;

        if ($count > $limit) {
            return "ephemeral:{$scopeKey}";
        }

        return $realFingerprintHash;
    }

    private function getIpPrefix(string $ip): string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $packed = inet_pton($ip);
            if ($packed !== false) {
                 $hex = bin2hex($packed);
                 // /64 = 16 hex chars
                 return substr($hex, 0, 16);
            }
        }
        return $ip;
    }
}
