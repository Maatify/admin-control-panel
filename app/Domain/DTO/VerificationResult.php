<?php

declare(strict_types=1);

namespace App\Domain\DTO;

use App\Domain\Enum\IdentityType;
use App\Domain\Enum\VerificationPurpose;

readonly class VerificationResult
{
    public function __construct(
        public bool $success,
        public string $reason = '',
        public ?IdentityType $identityType = null,
        public ?string $identityId = null,
        public ?VerificationPurpose $purpose = null
    ) {
    }

    public static function success(?IdentityType $identityType = null, ?string $identityId = null, ?VerificationPurpose $purpose = null): self
    {
        return new self(true, '', $identityType, $identityId, $purpose);
    }

    public static function failure(string $reason): self
    {
        return new self(false, $reason);
    }
}
