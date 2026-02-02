<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Security\Abuse;

use Maatify\AbuseProtection\Contracts\ChallengeProviderInterface;
use Maatify\AbuseProtection\DTO\AbuseContextDTO;
use Maatify\AbuseProtection\DTO\ChallengeResultDTO;

final readonly class TurnstileChallengeProvider implements ChallengeProviderInterface
{
    public function __construct(
        private string $secretKey,
        private int $timeoutSeconds = 3
    ) {}

    public function supports(string $context): bool
    {
        return $context === 'login';
    }

    public function verify(AbuseContextDTO $context, array $payload): ChallengeResultDTO
    {
        // Turnstile token field name (canonical)
        $token = $payload['cf-turnstile-response'] ?? null;

        // Backward-compat لو لسه عندك underscore
        if (!is_string($token) || $token === '') {
            $token = $payload['cf_turnstile_response'] ?? null;
        }

        if (!is_string($token) || $token === '') {
            return new ChallengeResultDTO(false, 'missing_challenge');
        }

        $remoteIp = $context->ipAddress;

        $postFields = http_build_query([
            'secret'   => $this->secretKey,
            'response' => $token,
            // remoteip optional
            'remoteip' => is_string($remoteIp) && $remoteIp !== '' ? $remoteIp : null,
        ]);

        $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
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

        if ($errno !== 0 || !is_string($raw) || $raw === '' || $httpCode < 200 || $httpCode >= 300) {
            // Security default: fail-closed when challenge is required
            return new ChallengeResultDTO(false, 'verification_failed');
        }

        $json = json_decode($raw, true);
        if (!is_array($json)) {
            return new ChallengeResultDTO(false, 'verification_failed');
        }

        $success = $json['success'] ?? false;
        if ($success === true) {
            return new ChallengeResultDTO(true);
        }

        // Optional error codes
        $codes = $json['error-codes'] ?? null;
        if (is_array($codes) && isset($codes[0]) && is_string($codes[0])) {
            return new ChallengeResultDTO(false, 'turnstile_' . $codes[0]);
        }

        return new ChallengeResultDTO(false, 'invalid_challenge');
    }
}
