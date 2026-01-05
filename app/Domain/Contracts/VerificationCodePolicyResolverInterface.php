<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\VerificationPolicy;
use App\Domain\Enum\VerificationPurpose;

interface VerificationCodePolicyResolverInterface
{
    public function resolve(VerificationPurpose $purpose): VerificationPolicy;
}
