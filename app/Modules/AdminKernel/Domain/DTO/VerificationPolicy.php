<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO;

readonly class VerificationPolicy
{
    /**
     * @param int $ttlSeconds Time to live in seconds
     * @param int $maxAttempts Maximum allowed attempts
     * @param int $resendCooldownSeconds Minimum seconds between resends
     */
    public function __construct(
        public int $ttlSeconds,
        public int $maxAttempts,
        public int $resendCooldownSeconds
    ) {
    }
}
