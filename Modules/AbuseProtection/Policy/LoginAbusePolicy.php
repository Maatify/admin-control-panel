<?php

declare(strict_types=1);

namespace Maatify\AbuseProtection\Policy;

use Maatify\AbuseProtection\Contracts\AbuseDecisionInterface;
use Maatify\AbuseProtection\DTO\AbuseContextDTO;

/**
 * Canonical login abuse policy.
 * No rate limiter dependency.
 * Pure decision logic.
 */
final readonly class LoginAbusePolicy implements AbuseDecisionInterface
{
    public function __construct(
        private int $challengeAfterFailures = 2,
    ) {}

    public function requiresChallenge(AbuseContextDTO $context): bool
    {
        if ($context->method !== 'POST') {
            return false;
        }

        if (!str_contains($context->route, 'login')) {
            return false;
        }

        return $context->failureCount >= $this->challengeAfterFailures;
    }
}
