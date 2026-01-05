<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Contracts\VerificationCodePolicyResolverInterface;
use App\Domain\DTO\VerificationPolicy;
use App\Domain\Enum\VerificationPurpose;

class VerificationCodePolicyResolver implements VerificationCodePolicyResolverInterface
{
    public function resolve(VerificationPurpose $purpose): VerificationPolicy
    {
        return match ($purpose) {
            VerificationPurpose::EMAIL_VERIFICATION => new VerificationPolicy(
                ttlSeconds: 600, // 10 minutes
                maxAttempts: 3,
                resendCooldownSeconds: 60
            ),
            VerificationPurpose::TELEGRAM_CHANNEL_LINK => new VerificationPolicy(
                ttlSeconds: 300, // 5 minutes
                maxAttempts: 3,
                resendCooldownSeconds: 60
            ),
        };
    }
}
