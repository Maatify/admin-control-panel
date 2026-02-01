<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\DTO;

class DeviceIdentityDTO
{
    public function __construct(
        public readonly ?string $fingerprintHash,
        public readonly string $confidence, // LOW, MEDIUM, HIGH
        public readonly bool $isTrustedSession,
        public readonly bool $churnDetected = false,
        public readonly string $normalizedUa = ''
    ) {}
}
