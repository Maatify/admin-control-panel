<?php

declare(strict_types=1);

namespace Maatify\Verification\Domain\DTO;

use Maatify\Verification\Domain\Enum\IdentityTypeEnum;
use Maatify\Verification\Domain\Enum\VerificationPurposeEnum;

readonly class VerificationResult
{
    public function __construct(
        public bool $success,
        public string $reason = '',
        public ?IdentityTypeEnum $identityType = null,
        public ?string $identityId = null,
        public ?VerificationPurposeEnum $purpose = null
    ) {
    }

    public static function success(?IdentityTypeEnum $identityType = null, ?string $identityId = null, ?VerificationPurposeEnum $purpose = null): self
    {
        return new self(true, '', $identityType, $identityId, $purpose);
    }

    public static function failure(string $reason): self
    {
        return new self(false, $reason);
    }
}
