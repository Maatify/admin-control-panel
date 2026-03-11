<?php

declare(strict_types=1);

namespace Maatify\Verification\Domain\Contracts;

use Maatify\Verification\Domain\DTO\VerificationResult;
use Maatify\Verification\Domain\Enum\IdentityTypeEnum;
use Maatify\Verification\Domain\Enum\VerificationPurposeEnum;

interface VerificationCodeValidatorInterface
{
    public function validate(IdentityTypeEnum $identityType, string $identityId, VerificationPurposeEnum $purpose, string $plainCode, ?string $usedIp = null): VerificationResult;

    public function validateByCode(string $plainCode, ?string $usedIp = null): VerificationResult;
}
