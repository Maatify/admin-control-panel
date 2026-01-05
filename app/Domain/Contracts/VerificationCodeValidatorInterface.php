<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\VerificationResult;
use App\Domain\Enum\IdentityTypeEnum;
use App\Domain\Enum\VerificationPurposeEnum;

interface VerificationCodeValidatorInterface
{
    public function validate(IdentityTypeEnum $identityType, string $identityId, VerificationPurposeEnum $purpose, string $plainCode): VerificationResult;

    public function validateByCode(string $plainCode): VerificationResult;
}
