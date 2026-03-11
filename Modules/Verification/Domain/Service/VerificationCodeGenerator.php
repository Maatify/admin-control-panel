<?php

declare(strict_types=1);

namespace Maatify\Verification\Domain\Service;

use Exception;
use Maatify\SharedCommon\Contracts\ClockInterface;
use Maatify\Verification\Domain\Contracts\VerificationCodeGeneratorInterface;
use Maatify\Verification\Domain\Contracts\VerificationCodePolicyResolverInterface;
use Maatify\Verification\Domain\Contracts\VerificationCodeRepositoryInterface;
use Maatify\Verification\Domain\DTO\GeneratedVerificationCode;
use Maatify\Verification\Domain\DTO\VerificationCode;
use Maatify\Verification\Domain\Enum\IdentityTypeEnum;
use Maatify\Verification\Domain\Enum\VerificationCodeStatus;
use Maatify\Verification\Domain\Enum\VerificationPurposeEnum;
use RuntimeException;

class VerificationCodeGenerator implements VerificationCodeGeneratorInterface
{
    public function __construct(
        private VerificationCodeRepositoryInterface $repository,
        private VerificationCodePolicyResolverInterface $policyResolver,
        private ClockInterface $clock
    ) {
    }

    public function generate(IdentityTypeEnum $identityType, string $identityId, VerificationPurposeEnum $purpose, ?string $createdIp = null): GeneratedVerificationCode
    {
        // 1. Resolve Policy
        $policy = $this->policyResolver->resolve($purpose);

        // 2. Invalidate previous active codes (Lifecycle Rule)
        $this->repository->expireAllFor($identityType, $identityId, $purpose);

        // 3. Generate random numeric OTP
        try {
            $plainCode = (string)random_int(100000, 999999);
        } catch (Exception $e) {
            throw new RuntimeException('Failed to generate secure random code.', 0, $e);
        }

        // 4. Hash
        $codeHash = hash('sha256', $plainCode);

        // 5. Create Entity
        $now = $this->clock->now();
        $expiresAt = $now->modify("+{$policy->ttlSeconds} seconds");

        $entity = new VerificationCode(
            0, // ID not yet assigned
            $identityType,
            $identityId,
            $purpose,
            $codeHash,
            VerificationCodeStatus::ACTIVE,
            0,
            $policy->maxAttempts,
            $expiresAt,
            $now,
            $createdIp
        );

        // 6. Store
        $this->repository->store($entity);

        // 7. Return (Entity + Plaintext)
        return new GeneratedVerificationCode($entity, $plainCode);
    }
}
