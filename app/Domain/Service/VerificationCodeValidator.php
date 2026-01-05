<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Contracts\VerificationCodeRepositoryInterface;
use App\Domain\Contracts\VerificationCodeValidatorInterface;
use App\Domain\DTO\VerificationResult;
use DateTimeImmutable;

class VerificationCodeValidator implements VerificationCodeValidatorInterface
{
    public function __construct(
        private VerificationCodeRepositoryInterface $repository
    ) {
    }

    public function validate(string $subjectType, string $subjectIdentifier, string $purpose, string $plainCode): VerificationResult
    {
        // 1. Find active code
        $code = $this->repository->findActive($subjectType, $subjectIdentifier, $purpose);

        if ($code === null) {
            // Generic failure
            return VerificationResult::failure('Invalid code.');
        }

        // 2. Check expiry
        if ($code->expiresAt < new DateTimeImmutable()) {
            $this->repository->expire($code->id);
            return VerificationResult::failure('Invalid code.');
        }

        // 3. Check attempts
        if ($code->attempts >= $code->maxAttempts) {
            $this->repository->expire($code->id);
            return VerificationResult::failure('Invalid code.');
        }

        // 4. Constant-time comparison
        $inputHash = hash('sha256', $plainCode);
        if (!hash_equals($code->codeHash, $inputHash)) {
            // Increment attempts on failure
            $this->repository->incrementAttempts($code->id);
            // Check if this attempt exceeded max
            if ($code->attempts + 1 >= $code->maxAttempts) {
                $this->repository->expire($code->id);
            }
            return VerificationResult::failure('Invalid code.');
        }

        // 5. Mark used on success
        $this->repository->markUsed($code->id);

        return VerificationResult::success();
    }
}
