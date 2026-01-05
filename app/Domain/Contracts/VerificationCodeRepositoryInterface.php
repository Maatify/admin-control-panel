<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\VerificationCode;
use App\Domain\Enum\IdentityTypeEnum;
use App\Domain\Enum\VerificationPurposeEnum;

interface VerificationCodeRepositoryInterface
{
    public function store(VerificationCode $code): void;

    public function findActive(IdentityTypeEnum $identityType, string $identityId, VerificationPurposeEnum $purpose): ?VerificationCode;

    public function findByCodeHash(string $codeHash): ?VerificationCode;

    public function incrementAttempts(int $codeId): void;

    public function markUsed(int $codeId): void;

    public function expire(int $codeId): void;

    public function expireAllFor(IdentityTypeEnum $identityType, string $identityId, VerificationPurposeEnum $purpose): void;
}
