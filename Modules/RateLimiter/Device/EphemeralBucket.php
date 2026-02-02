<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\Device;

use Maatify\RateLimiter\Contract\CorrelationStoreInterface;
use Maatify\RateLimiter\DTO\EphemeralStateDTO;
use Maatify\RateLimiter\DTO\RateLimitContextDTO;

class EphemeralBucket
{
    private const MAX_DEVICES_PER_ACCOUNT = 10;
    private const MAX_DEVICES_PER_IP = 50;
    private const CAP_WINDOW = 900; // 15 mins (Flood Window)

    public function __construct(
        private readonly CorrelationStoreInterface $store
    ) {}

    public function resolveKey(RateLimitContextDTO $context, string $realFingerprintHash): string
    {
        // Check state but return key string
        // We must perform the check to know if we should return ephemeral key
        $state = $this->check($context, $realFingerprintHash);

        if ($state->isEphemeral) {
            // Re-derive scope key for ephemeral ID
            // Since we don't return the exact ephemeral key from check(), we reconstruct it.
            // Or better, check() could return it? No, check returns DTO.
            // Logic:
            $ipScope = $this->getIpPrefix($context->ip);
            if ($context->accountId && $state->accountDeviceCount > self::MAX_DEVICES_PER_ACCOUNT) {
                return "ephemeral:dev_cap:acc:{$context->accountId}:{$ipScope}";
            }
            return "ephemeral:dev_cap:ip:{$ipScope}";
        }

        return $realFingerprintHash;
    }

    public function check(RateLimitContextDTO $context, string $realFingerprintHash): EphemeralStateDTO
    {
        // Scope Key Calculation (IPv6 Prefix)
        $ipScope = $this->getIpPrefix($context->ip);

        // Track per-account (Scoped to IP Prefix)
        $accCount = 0;
        if ($context->accountId) {
            $accKey = "dev_cap:acc:{$context->accountId}:{$ipScope}";
            $accCount = $this->store->addDistinct($accKey, $realFingerprintHash, self::CAP_WINDOW);
        }

        // Track per-IP
        $ipKey = "dev_cap:ip:{$ipScope}";
        $ipCount = $this->store->addDistinct($ipKey, $realFingerprintHash, self::CAP_WINDOW);

        $isEphemeral = false;
        if ($context->accountId && $accCount > self::MAX_DEVICES_PER_ACCOUNT) {
            $isEphemeral = true;
        }
        if ($ipCount > self::MAX_DEVICES_PER_IP) {
            $isEphemeral = true;
        }

        return new EphemeralStateDTO($isEphemeral, $accCount, $ipCount);
    }

    private function getIpPrefix(string $ip): string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $packed = inet_pton($ip);
            if ($packed !== false) {
                 $hex = bin2hex($packed);
                 return substr($hex, 0, 16); // /64
            }
        }
        return $ip;
    }
}
