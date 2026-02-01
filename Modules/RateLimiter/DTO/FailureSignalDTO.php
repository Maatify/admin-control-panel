<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\DTO;

class FailureSignalDTO
{
    public const TYPE_CB_OPENED = 'CB_OPENED';
    public const TYPE_CB_RECOVERED = 'CB_RECOVERED';
    public const TYPE_CB_RE_ENTRY_VIOLATION = 'CB_RE_ENTRY_VIOLATION';

    public function __construct(
        public readonly string $type,
        public readonly string $policyName,
        public readonly array $metadata = []
    ) {}
}
