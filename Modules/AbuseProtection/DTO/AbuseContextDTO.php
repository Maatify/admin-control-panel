<?php

declare(strict_types=1);

namespace Maatify\AbuseProtection\DTO;

/**
 * Immutable request-level context.
 * No auth. No identity. No session semantics.
 */
final readonly class AbuseContextDTO
{
    public function __construct(
        public string $route,
        public string $method,
        public ?string $ipAddress,
        public ?string $userAgent,
        public int $failureCount
    ) {}
}
