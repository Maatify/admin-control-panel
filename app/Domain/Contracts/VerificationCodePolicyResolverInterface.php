<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\VerificationPolicy;

interface VerificationCodePolicyResolverInterface
{
    public function resolve(string $purpose): VerificationPolicy;
}
