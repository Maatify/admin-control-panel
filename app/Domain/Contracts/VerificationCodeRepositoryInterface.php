<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\VerificationCode;

interface VerificationCodeRepositoryInterface
{
    public function store(VerificationCode $code): void;

    public function findActive(string $identityType, string $identityId, string $purpose): ?VerificationCode;

    public function findByCodeHash(string $codeHash): ?VerificationCode;

    public function incrementAttempts(int $codeId): void;

    public function markUsed(int $codeId): void;

    public function expire(int $codeId): void;

    public function expireAllFor(string $identityType, string $identityId, string $purpose): void;
}
