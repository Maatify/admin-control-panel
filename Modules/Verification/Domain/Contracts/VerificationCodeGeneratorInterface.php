<?php

declare(strict_types=1);

namespace Maatify\Verification\Domain\Contracts;

use Maatify\Verification\Domain\DTO\GeneratedVerificationCode;
use Maatify\Verification\Domain\Enum\IdentityTypeEnum;
use Maatify\Verification\Domain\Enum\VerificationPurposeEnum;

interface VerificationCodeGeneratorInterface
{
    /**
     * Generates a new verification code, invalidating previous ones.
     */
    public function generate(IdentityTypeEnum $identityType, string $identityId, VerificationPurposeEnum $purpose, ?string $createdIp = null): GeneratedVerificationCode;
}
