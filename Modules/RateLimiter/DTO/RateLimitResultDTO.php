<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\DTO;

use Maatify\RateLimiter\DTO\RateLimitMetadataDTO;

class RateLimitResultDTO
{
    public const DECISION_ALLOW = 'ALLOW';
    public const DECISION_SOFT_BLOCK = 'SOFT_BLOCK';
    public const DECISION_HARD_BLOCK = 'HARD_BLOCK';

    public function __construct(
        public readonly string $decision,
        public readonly ?int $blockLevel,
        public readonly ?int $retryAfter, // in seconds
        public readonly string $failureMode, // NORMAL, DEGRADED, FAIL_OPEN
        public readonly ?RateLimitMetadataDTO $metadata = null
    ) {}

    public function isAllowed(): bool
    {
        return $this->decision === self::DECISION_ALLOW;
    }

    public function isBlocked(): bool
    {
        return $this->decision !== self::DECISION_ALLOW;
    }
}
