<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Contracts\VerificationCodeRepositoryInterface;
use App\Domain\Contracts\VerificationCodeValidatorInterface;
use App\Domain\DTO\VerificationResult;
use App\Domain\Enum\IdentityTypeEnum;
use App\Domain\Enum\VerificationCodeStatus;
use App\Domain\Enum\VerificationPurposeEnum;
use Maatify\SharedCommon\Contracts\ClockInterface;

class VerificationCodeValidator implements VerificationCodeValidatorInterface
{
    public function __construct(
        private VerificationCodeRepositoryInterface $repository,
        private ClockInterface $clock
    ) {
    }

    public function validate(IdentityTypeEnum $identityType, string $identityId, VerificationPurposeEnum $purpose, string $plainCode): VerificationResult
    {
        // 1. Find active code
        $code = $this->repository->findActive($identityType, $identityId, $purpose);

        if ($code === null) {
            return VerificationResult::failure('Invalid code.');
        }

        // 2. Check expiry
        if ($code->expiresAt < $this->clock->now()) {
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

        return VerificationResult::success($code->identityType, $code->identityId, $code->purpose);
    }

    public function validateByCode(string $plainCode): VerificationResult
    {
        // 1. Hash the input
        $codeHash = hash('sha256', $plainCode);

        // 2. Lookup by hash
        $code = $this->repository->findByCodeHash($codeHash);

        if ($code === null) {
            // No matching code found (or hash mismatch implies not found)
            return VerificationResult::failure('Invalid code.');
        }

        // 3. Check status
        if ($code->status !== VerificationCodeStatus::ACTIVE) {
            return VerificationResult::failure('Invalid code.');
        }

        // 4. Check expiry
        if ($code->expiresAt < $this->clock->now()) {
            $this->repository->expire($code->id);
            return VerificationResult::failure('Invalid code.');
        }

        // 5. Check attempts
        // Even if hash matches, maybe it was locked out previously?
        if ($code->attempts >= $code->maxAttempts) {
            $this->repository->expire($code->id);
            return VerificationResult::failure('Invalid code.');
        }

        // 6. Success -> Mark used
        $this->repository->markUsed($code->id);

        return VerificationResult::success($code->identityType, $code->identityId, $code->purpose);
    }
}
