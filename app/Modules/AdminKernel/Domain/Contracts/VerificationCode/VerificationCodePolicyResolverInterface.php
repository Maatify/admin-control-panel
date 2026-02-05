<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Contracts\VerificationCode;

use Maatify\AdminKernel\Domain\DTO\VerificationPolicy;
use Maatify\AdminKernel\Domain\Enum\VerificationPurposeEnum;

interface VerificationCodePolicyResolverInterface
{
    public function resolve(VerificationPurposeEnum $purpose): VerificationPolicy;
}
