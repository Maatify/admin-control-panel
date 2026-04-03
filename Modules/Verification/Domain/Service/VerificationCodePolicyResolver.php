<?php

declare(strict_types=1);

namespace Maatify\Verification\Domain\Service;

use Maatify\Verification\Domain\Contracts\VerificationCodePolicyResolverInterface;
use Maatify\Verification\Domain\DTO\VerificationPolicy;
use Maatify\Verification\Domain\Enum\VerificationPurposeEnum;

class VerificationCodePolicyResolver implements VerificationCodePolicyResolverInterface
{
    public function resolve(VerificationPurposeEnum $purpose): VerificationPolicy
    {
        return match ($purpose) {
            VerificationPurposeEnum::EmailVerification => new VerificationPolicy(
                ttlSeconds: 600, // 10 minutes
                maxAttempts: 3,
                resendCooldownSeconds: 60
            ),
            VerificationPurposeEnum::TelegramChannelLink => new VerificationPolicy(
                ttlSeconds: 300, // 5 minutes
                maxAttempts: 3,
                resendCooldownSeconds: 60
            ),
        };
    }
}
