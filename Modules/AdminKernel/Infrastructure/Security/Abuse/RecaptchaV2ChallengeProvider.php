<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Security\Abuse;

use Maatify\AbuseProtection\Contracts\ChallengeProviderInterface;
use Maatify\AbuseProtection\DTO\AbuseContextDTO;
use Maatify\AbuseProtection\DTO\ChallengeResultDTO;

/**
 * RecaptchaV2ChallengeProvider
 *
 * Verifies Google reCAPTCHA v2 challenge tokens.
 *
 * This provider performs synchronous verification against
 * Google's siteverify endpoint.
 *
 * Failures are soft and mapped to ChallengeResultDTO reasons.
 */
final readonly class RecaptchaV2ChallengeProvider implements ChallengeProviderInterface
{
    public function __construct(
        private string $secretKey,
        private int $timeoutSeconds = 3
    ) {}

    public function supports(string $context): bool
    {
        return $context === 'login';
    }

    public function verify(
        AbuseContextDTO $context,
        array $payload
    ): ChallengeResultDTO {

        $token = $payload['g-recaptcha-response'] ?? null;

        if (! is_string($token) || $token === '') {
            return new ChallengeResultDTO(false, 'missing_challenge');
        }

        $postFields = http_build_query([
            'secret'   => $this->secretKey,
            'response' => $token,
            'remoteip' => $context->ipAddress,
        ]);

        $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
        if ($ch === false) {
            return new ChallengeResultDTO(false, 'verification_unavailable');
        }

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postFields,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeoutSeconds,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        ]);

        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0 || ! is_string($raw) || $httpCode < 200 || $httpCode >= 300) {
            return new ChallengeResultDTO(false, 'verification_failed');
        }

        $json = json_decode($raw, true);
        if (! is_array($json) || ! ($json['success'] ?? false)) {
            return new ChallengeResultDTO(false, 'invalid_challenge');
        }

        return new ChallengeResultDTO(true);
    }
}
