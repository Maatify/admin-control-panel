<?php

declare(strict_types=1);

namespace Maatify\AbuseProtection\Contracts;

use Maatify\AbuseProtection\DTO\AbuseContextDTO;
use Maatify\AbuseProtection\DTO\ChallengeResultDTO;

/**
 * Provider contract for any abuse challenge mechanism
 * (CAPTCHA, Turnstile, Proof-of-Work, JS Challenge, etc.)
 */
interface ChallengeProviderInterface
{
    public function supports(string $context): bool;

    /**
     * @param array<string, mixed> $payload
     */
    public function verify(
        AbuseContextDTO $context,
        array $payload
    ): ChallengeResultDTO;
}
