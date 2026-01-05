<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\VerificationResult;

interface VerificationCodeValidatorInterface
{
    public function validate(string $identityType, string $identityId, string $purpose, string $plainCode): VerificationResult;

    public function validateByCode(string $plainCode): VerificationResult;
}
