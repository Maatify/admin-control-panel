<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Contracts;

use Maatify\AdminKernel\Domain\DTO\VerificationResult;
use Maatify\AdminKernel\Domain\Enum\IdentityTypeEnum;
use Maatify\AdminKernel\Domain\Enum\VerificationPurposeEnum;

interface VerificationCodeValidatorInterface
{
    public function validate(IdentityTypeEnum $identityType, string $identityId, VerificationPurposeEnum $purpose, string $plainCode): VerificationResult;

    public function validateByCode(string $plainCode): VerificationResult;
}
