<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\DTO;

class RateLimitMetadataDTO
{
    public function __construct(
        public readonly ?string $signal = null,
        public readonly ?string $cause = null, // e.g., 're_entry_violation', 'circuit_breaker_open'
        public readonly array $context = [] // Kept for flexible extra context if absolutely needed, but signal is primary
    ) {}
}
