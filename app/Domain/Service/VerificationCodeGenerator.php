<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Contracts\VerificationCodeGeneratorInterface;
use App\Domain\Contracts\VerificationCodePolicyResolverInterface;
use App\Domain\Contracts\VerificationCodeRepositoryInterface;
use App\Domain\DTO\GeneratedVerificationCode;
use App\Domain\DTO\VerificationCode;
use App\Domain\Enum\VerificationCodeStatus;
use DateTimeImmutable;
use Exception;
use RuntimeException;

class VerificationCodeGenerator implements VerificationCodeGeneratorInterface
{
    public function __construct(
        private VerificationCodeRepositoryInterface $repository,
        private VerificationCodePolicyResolverInterface $policyResolver
    ) {
    }

    public function generate(string $subjectType, string $subjectIdentifier, string $purpose): GeneratedVerificationCode
    {
        // 1. Resolve Policy
        $policy = $this->policyResolver->resolve($purpose);

        // 2. Invalidate previous active codes (Lifecycle Rule)
        $this->repository->expireAllFor($subjectType, $subjectIdentifier, $purpose);

        // 3. Generate random numeric OTP
        try {
            $plainCode = (string)random_int(100000, 999999);
        } catch (Exception $e) {
            throw new RuntimeException('Failed to generate secure random code.', 0, $e);
        }

        // 4. Hash
        // Using SHA-256 as requested in rules.
        $codeHash = hash('sha256', $plainCode);

        // 5. Create Entity
        $now = new DateTimeImmutable();
        $expiresAt = $now->modify("+{$policy->ttlSeconds} seconds");

        $entity = new VerificationCode(
            0, // ID not yet assigned
            $subjectType,
            $subjectIdentifier,
            $purpose,
            $codeHash,
            VerificationCodeStatus::ACTIVE,
            0,
            $policy->maxAttempts,
            $expiresAt,
            $now
        );

        // 6. Store
        $this->repository->store($entity);

        // 7. Return (Entity + Plaintext)
        return new GeneratedVerificationCode($entity, $plainCode);
    }
}
