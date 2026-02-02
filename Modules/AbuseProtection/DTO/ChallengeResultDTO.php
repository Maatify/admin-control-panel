<?php

declare(strict_types=1);

namespace Maatify\AbuseProtection\DTO;

final readonly class ChallengeResultDTO
{
    public function __construct(
        public bool $passed,
        public ?string $reason = null
    ) {}
}
