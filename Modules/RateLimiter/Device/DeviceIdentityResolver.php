<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\Device;

use Maatify\RateLimiter\Contract\DeviceIdentityResolverInterface;
use Maatify\RateLimiter\DTO\DeviceIdentityDTO;
use Maatify\RateLimiter\DTO\RateLimitContextDTO;

class DeviceIdentityResolver implements DeviceIdentityResolverInterface
{
    public function __construct(
        private readonly FingerprintHasher $hasher
    ) {}

    public function resolve(RateLimitContextDTO $context): DeviceIdentityDTO
    {
        $ua = $this->normalizeUserAgent($context->ua);
        $clientFp = $context->clientFingerprint ? $this->normalizeClientFp($context->clientFingerprint) : '';
        $sessionFp = $context->sessionDeviceId ?? '';

        $confidence = 'LOW';
        if (!empty($clientFp)) {
            $confidence = 'MEDIUM';
        }
        if (!empty($sessionFp) && $context->isSessionTrusted) {
            $confidence = 'HIGH';
        }

        $rawString = "v1|{$ua}|{$clientFp}|{$sessionFp}";
        $hash = $this->hasher->hash($rawString);

        return new DeviceIdentityDTO(
            $hash,
            $confidence,
            $context->isSessionTrusted,
            false,
            $ua
        );
    }

    public static function normalizeUserAgent(string $ua): string
    {
        if (preg_match('#(Chrome|Firefox|Safari|Edge|OPR)/(\d+)#', $ua, $matches)) {
            return strtolower($matches[1] . '/' . $matches[2]);
        }
        return strtolower(substr($ua, 0, 50));
    }

    /**
     * @param array<string, mixed> $fp
     */
    private function normalizeClientFp(array $fp): string
    {
        ksort($fp);
        $json = json_encode($fp);
        return $json === false ? '' : $json;
    }
}
