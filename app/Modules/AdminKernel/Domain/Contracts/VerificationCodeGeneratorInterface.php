<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Contracts;

use Maatify\AdminKernel\Domain\DTO\GeneratedVerificationCode;
use Maatify\AdminKernel\Domain\Enum\IdentityTypeEnum;
use Maatify\AdminKernel\Domain\Enum\VerificationPurposeEnum;

interface VerificationCodeGeneratorInterface
{
    /**
     * Generates a new verification code, invalidating previous ones.
     */
    public function generate(IdentityTypeEnum $identityType, string $identityId, VerificationPurposeEnum $purpose): GeneratedVerificationCode;
}
