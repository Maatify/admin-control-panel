<?php

declare(strict_types=1);

namespace Maatify\AbuseProtection\Providers;

use Maatify\AbuseProtection\Contracts\ChallengeProviderInterface;
use Maatify\AbuseProtection\DTO\AbuseContextDTO;
use Maatify\AbuseProtection\DTO\ChallengeResultDTO;

/**
 * Safe default provider.
 * Always passes.
 */
final class NullProvider implements ChallengeProviderInterface
{
    public function supports(string $context): bool
    {
        return true;
    }

    public function verify(
        AbuseContextDTO $context,
        array $payload
    ): ChallengeResultDTO {
        return new ChallengeResultDTO(true);
    }
}
