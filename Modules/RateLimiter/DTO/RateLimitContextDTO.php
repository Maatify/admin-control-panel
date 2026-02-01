<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\DTO;

class RateLimitContextDTO
{
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
