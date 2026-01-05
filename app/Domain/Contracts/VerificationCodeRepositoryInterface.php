<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\VerificationCode;
use App\Domain\Enum\IdentityType;
use App\Domain\Enum\VerificationPurpose;

interface VerificationCodeRepositoryInterface
{
    public function store(VerificationCode $code): void;

    public function findActive(IdentityType $identityType, string $identityId, VerificationPurpose $purpose): ?VerificationCode;

    public function findByCodeHash(string $codeHash): ?VerificationCode;

    public function incrementAttempts(int $codeId): void;

    public function markUsed(int $codeId): void;

    public function expire(int $codeId): void;

    public function expireAllFor(IdentityType $identityType, string $identityId, VerificationPurpose $purpose): void;
}
