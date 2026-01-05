<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\VerificationResult;
use App\Domain\Enum\IdentityType;
use App\Domain\Enum\VerificationPurpose;

interface VerificationCodeValidatorInterface
{
    public function validate(IdentityType $identityType, string $identityId, VerificationPurpose $purpose, string $plainCode): VerificationResult;

    public function validateByCode(string $plainCode): VerificationResult;
}
