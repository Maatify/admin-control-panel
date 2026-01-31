<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO;

readonly class TotpVerificationResultDTO
{
    public function __construct(
        public bool $success,
        public ?string $errorReason = null
    ) {
    }
}
