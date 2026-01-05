<?php

declare(strict_types=1);

namespace App\Domain\DTO;

readonly class VerificationResult
{
    public function __construct(
        public bool $success,
        public string $reason = '',
        public ?string $identityType = null,
        public ?string $identityId = null,
        public ?string $purpose = null
    ) {
    }

    public static function success(?string $identityType = null, ?string $identityId = null, ?string $purpose = null): self
    {
        return new self(true, '', $identityType, $identityId, $purpose);
    }

    public static function failure(string $reason): self
    {
        return new self(false, $reason);
    }
}
