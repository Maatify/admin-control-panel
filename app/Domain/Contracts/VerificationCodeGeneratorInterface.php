<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\GeneratedVerificationCode;

interface VerificationCodeGeneratorInterface
{
    /**
     * Generates a new verification code, invalidating previous ones.
     */
    public function generate(string $identityType, string $identityId, string $purpose): GeneratedVerificationCode;
}
