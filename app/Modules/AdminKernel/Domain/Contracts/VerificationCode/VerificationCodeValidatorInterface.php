<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Contracts\VerificationCode;

use Maatify\AdminKernel\Domain\DTO\VerificationResult;
use Maatify\AdminKernel\Domain\Enum\IdentityTypeEnum;
use Maatify\AdminKernel\Domain\Enum\VerificationPurposeEnum;

interface VerificationCodeValidatorInterface
{
    public function validate(IdentityTypeEnum $identityType, string $identityId, VerificationPurposeEnum $purpose, string $plainCode, ?string $usedIp = null): VerificationResult;

    public function validateByCode(string $plainCode, ?string $usedIp = null): VerificationResult;
}
