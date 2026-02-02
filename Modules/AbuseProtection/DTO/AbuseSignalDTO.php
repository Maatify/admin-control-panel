<?php

declare(strict_types=1);

namespace Maatify\AbuseProtection\DTO;

/**
 * Represents a client-side abuse signal.
 *
 * This DTO is intentionally simple and serializable.
 */
final readonly class AbuseSignalDTO
{
    public function __construct(
        public int $failureCount,
        public int $expiresAt,
        public string $keyId,
        public string $signature
    ) {}
}
