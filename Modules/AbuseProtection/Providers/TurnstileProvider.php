<?php

declare(strict_types=1);

namespace Maatify\AbuseProtection\Providers;

use Maatify\AbuseProtection\Contracts\ChallengeProviderInterface;
use Maatify\AbuseProtection\DTO\AbuseContextDTO;
use Maatify\AbuseProtection\DTO\ChallengeResultDTO;

/**
 * Cloudflare Turnstile provider.
 * HTTP client intentionally NOT included (library-safe).
 *
 * NOTE:
 * The module ships with a conceptual TurnstileProvider stub.
 * Host applications MUST provide the actual HTTP verification adapter.
 */
final class TurnstileProvider implements ChallengeProviderInterface
{
    /**
     * @phpstan-ignore-next-line property.onlyWritten
     */
    public function __construct(private string $secretKey // intentionally unused (verification adapter injected later)
    ) {}

    public function supports(string $context): bool
    {
        return $context === 'login';
    }

    public function verify(
        AbuseContextDTO $context,
        array $payload
    ): ChallengeResultDTO {
        if (!isset($payload['cf_turnstile_response'])) {
            return new ChallengeResultDTO(false, 'missing_challenge');
        }

        // NOTE:
        // HTTP verification intentionally abstracted.
        // Host application must inject verification adapter.

        return new ChallengeResultDTO(true);
    }
}
