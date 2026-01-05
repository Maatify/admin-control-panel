<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\GeneratedVerificationCode;
use App\Domain\Enum\IdentityType;
use App\Domain\Enum\VerificationPurpose;

interface VerificationCodeGeneratorInterface
{
    /**
     * Generates a new verification code, invalidating previous ones.
     */
    public function generate(IdentityType $identityType, string $identityId, VerificationPurpose $purpose): GeneratedVerificationCode;
}
