<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\DTO;

class EphemeralStateDTO
{
    public function __construct(
        public readonly bool $isEphemeral,
        public readonly int $accountDeviceCount,
        public readonly int $ipDeviceCount
    ) {}
}
