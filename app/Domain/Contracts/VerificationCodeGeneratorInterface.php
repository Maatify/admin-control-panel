<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\GeneratedVerificationCode;
use App\Domain\Enum\IdentityTypeEnum;
use App\Domain\Enum\VerificationPurposeEnum;

interface VerificationCodeGeneratorInterface
{
    /**
     * Generates a new verification code, invalidating previous ones.
     */
    public function generate(IdentityTypeEnum $identityType, string $identityId, VerificationPurposeEnum $purpose): GeneratedVerificationCode;
}
