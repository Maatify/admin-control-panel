<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\VerificationCode;

interface VerificationCodeRepositoryInterface
{
    /**
     * Store a new verification code.
     * The ID is not yet available, but the implementation should handle insertion.
     * For simplicity with DTOs, we might pass the parameters or a transient DTO.
     * But adhering to the interface defined in prompt: store(VerificationCode).
     * Since ID is auto-increment, we usually pass an entity without ID, or return ID.
     * The prompt implies store(VerificationCode). We will assume the DTO passed in might have ID=0 or null if it's new,
     * or we pass the params.
     * To strict adhere to "store(VerificationCode)", we will assume the DTO is fully formed,
     * but usually for new records we don't have ID.
     * I will adjust to store(string $subjectType, ...).
     * Wait, prompt says: `store(VerificationCode)`.
     * I will assume the `id` in `VerificationCode` can be 0 for new records.
     */
    public function store(VerificationCode $code): void;

    public function findActive(string $subjectType, string $subjectIdentifier, string $purpose): ?VerificationCode;

    public function incrementAttempts(int $codeId): void;

    public function markUsed(int $codeId): void;

    public function expire(int $codeId): void;

    public function expireAllFor(string $subjectType, string $subjectIdentifier, string $purpose): void;
}
