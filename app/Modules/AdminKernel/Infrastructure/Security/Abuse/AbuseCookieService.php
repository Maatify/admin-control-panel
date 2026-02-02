<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Security\Abuse;

use Maatify\AbuseProtection\Contracts\AbuseSignatureProviderInterface;
use Maatify\AdminKernel\Context\RequestContext;
use Maatify\AdminKernel\Domain\Contracts\Abuse\AbuseCookieServiceInterface;
use Maatify\AdminKernel\Domain\DTO\Abuse\AbuseCookieIssueDTO;

final readonly class AbuseCookieService implements AbuseCookieServiceInterface
{
    public function __construct(
        private AbuseSignatureProviderInterface $signatureProvider,
    ) {
    }

    public function issue(string $sessionToken, RequestContext $context, ?string $existingDeviceId): AbuseCookieIssueDTO
    {
        $issuedAt = time();

        // 1) Device ID (stable cookie)
        $deviceId = $existingDeviceId;
        if (!is_string($deviceId) || $deviceId === '') {
            // 128-bit random hex
            $deviceId = bin2hex(random_bytes(16));
        }

        // 2) Session ID derived from token (server-friendly, avoids raw token reuse)
        $sessionId = hash('sha256', $sessionToken);

        // 3) Normalize context inputs (no secrets, no raw UA/IP storage)
        $ip = (string) ($context->ipAddress ?? '');
        $ua = (string) ($context->userAgent ?? '');

        // Optional: reduce sensitivity of IP (e.g., /24) - safe default: hash whole ip string
        $ipHash = hash('sha256', $ip);
        $uaHash = hash('sha256', $ua);

        // 4) Payload to sign (stable order)
        $payload = implode('|', [
            'v1',
            $deviceId,
            $sessionId,
            $ipHash,
            $uaHash,
            (string) $issuedAt,
        ]);

        // 5) Signature (HMAC / HKDF-based impl behind provider)
        $signature = $this->signatureProvider->sign($payload);

        // TTLs
        $deviceTtl = 60 * 60 * 24 * 365; // 1 year
        $sigTtl = 60 * 60 * 24 * 7;      // 7 days (tunable)

        return new AbuseCookieIssueDTO(
            deviceId: $deviceId,
            deviceTtlSeconds: $deviceTtl,
            signature: $signature,
            signatureTtlSeconds: $sigTtl,
            issuedAtUnix: $issuedAt,
        );
    }
}
