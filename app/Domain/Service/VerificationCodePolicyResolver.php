<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Contracts\VerificationCodePolicyResolverInterface;
use App\Domain\DTO\VerificationPolicy;

class VerificationCodePolicyResolver implements VerificationCodePolicyResolverInterface
{
    public function resolve(string $purpose): VerificationPolicy
    {
        // In a real app, this might come from config or DB.
        // Hardcoding based on purpose for now.

        return match ($purpose) {
            'email_verification' => new VerificationPolicy(
                ttlSeconds: 600, // 10 minutes
                maxAttempts: 3,
                resendCooldownSeconds: 60
            ),
            'telegram_link' => new VerificationPolicy(
                ttlSeconds: 300, // 5 minutes
                maxAttempts: 3,
                resendCooldownSeconds: 60
            ),
            default => new VerificationPolicy(
                ttlSeconds: 300,
                maxAttempts: 3,
                resendCooldownSeconds: 60
            ),
        };
    }
}
