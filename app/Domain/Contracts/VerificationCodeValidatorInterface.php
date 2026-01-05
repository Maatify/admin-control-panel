<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\VerificationResult;

interface VerificationCodeValidatorInterface
{
    public function validate(string $subjectType, string $subjectIdentifier, string $purpose, string $plainCode): VerificationResult;
}
