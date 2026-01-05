<?php

declare(strict_types=1);

namespace App\Domain\DTO;

readonly class TotpVerificationResultDTO
{
    public function __construct(
        public bool $success,
        public ?string $errorReason = null
    ) {
    }
}
