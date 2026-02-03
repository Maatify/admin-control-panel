<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\DTO;

class RateLimitContextDTO
{
    /**
     * @param string $ip
     * @param string $ua
     * @param ?string $accountId
     * @param ?array<string, mixed> $clientFingerprint
     * @param ?string $sessionDeviceId
     * @param bool $isSessionTrusted
     * @param array<string, string|string[]> $headers
     */
    public function __construct(
        public readonly string $ip,
        public readonly string $ua,
        public readonly ?string $accountId = null,
        public readonly ?array $clientFingerprint = null,
        public readonly ?string $sessionDeviceId = null,
        public readonly bool $isSessionTrusted = false,
        public readonly array $headers = []
    ) {}
}
