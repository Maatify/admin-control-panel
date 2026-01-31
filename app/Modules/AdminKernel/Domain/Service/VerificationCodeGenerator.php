<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Service;

use Maatify\AdminKernel\Domain\Contracts\VerificationCodeGeneratorInterface;
use Maatify\AdminKernel\Domain\Contracts\VerificationCodePolicyResolverInterface;
use Maatify\AdminKernel\Domain\Contracts\VerificationCodeRepositoryInterface;
use Maatify\AdminKernel\Domain\DTO\GeneratedVerificationCode;
use Maatify\AdminKernel\Domain\DTO\VerificationCode;
use Maatify\AdminKernel\Domain\Enum\IdentityTypeEnum;
use Maatify\AdminKernel\Domain\Enum\VerificationCodeStatus;
use Maatify\AdminKernel\Domain\Enum\VerificationPurposeEnum;
use Maatify\SharedCommon\Contracts\ClockInterface;
use Exception;
use RuntimeException;

class VerificationCodeGenerator implements VerificationCodeGeneratorInterface
{
    public function __construct(
        private VerificationCodeRepositoryInterface $repository,
        private VerificationCodePolicyResolverInterface $policyResolver,
        private ClockInterface $clock
    ) {
    }

    public function generate(IdentityTypeEnum $identityType, string $identityId, VerificationPurposeEnum $purpose): GeneratedVerificationCode
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
            $now
        );

        // 6. Store
        $this->repository->store($entity);

        // 7. Return (Entity + Plaintext)
        return new GeneratedVerificationCode($entity, $plainCode);
    }
}
