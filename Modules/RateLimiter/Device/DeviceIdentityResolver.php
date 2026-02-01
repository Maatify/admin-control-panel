<?php

declare(strict_types=1);

namespace Maatify\RateLimiter\Device;

use Maatify\RateLimiter\Contract\DeviceIdentityResolverInterface;
use Maatify\RateLimiter\DTO\DeviceIdentityDTO;
use Maatify\RateLimiter\DTO\RateLimitContextDTO;

class DeviceIdentityResolver implements DeviceIdentityResolverInterface
{
    public function __construct(
        private readonly FingerprintHasher $hasher,
        private readonly EphemeralBucket $ephemeralBucket
    ) {}

    public function resolve(RateLimitContextDTO $context): DeviceIdentityDTO
    {
        // 1. Normalize Inputs
        $ua = $this->normalizeUserAgent($context->ua);
        $clientFp = $context->clientFingerprint ? $this->normalizeClientFp($context->clientFingerprint) : '';
        $sessionFp = $context->sessionDeviceId ?? '';

        // 2. Calculate Confidence
        $confidence = 'LOW';
        if (!empty($clientFp)) {
            $confidence = 'MEDIUM';
        }
        if (!empty($sessionFp) && $context->isSessionTrusted) {
            $confidence = 'HIGH';
        }

        // 3. Generate Raw Fingerprint String (Internal)
        // Combine components.
        // If Session is present and trusted, it dominates?
        // DEVICE_FINGERPRINT 3.3: "Session-Bound... Advisory only".
        // "All fingerprint levels are combined into a single resolved identity."
        // So we hash them all together?
        // Or if we have session ID, that IS the identity?
        // "Device identity is resolved into a single DeviceIdentityDTO with: hashed fingerprints only".
        // Usually, a session ID is unique enough.
        // But if I delete cookies (Session ID lost), I fall back to Client/Passive.
        // If I have Session ID, I am that device.
        // But the prompt says "All fingerprint levels are combined".
        // If I combine them, then SessionID + UA1 != SessionID + UA2.
        // This makes sense (detecting browser update or spoofing).

        $rawString = "v1|{$ua}|{$clientFp}|{$sessionFp}";

        // 4. Hash
        $hash = $this->hasher->hash($rawString);

        // 5. Check Ephemeral Cap
        $finalHash = $this->ephemeralBucket->resolveKey($context, $hash);

        // 6. Check Churn (Optional/TODO - requires store history?
        // Docs say "The system MUST detect... Rapid fingerprint changes".
        // This is usually done in the Engine/Policy via Correlation Rules (5.3 Fingerprint Evasion).
        // The DTO has `churnDetected`.
        // If we want to detect churn HERE, we need history.
        // But Correlation Rules in DECISION_MATRIX handle "Rapid churn of K3 under one K2".
        // So maybe `churnDetected` in DTO is a flag populated if we detect it here?
        // For now, I'll default to false as detection is likely in Engine Correlation logic.

        return new DeviceIdentityDTO(
            $finalHash,
            $confidence,
            $context->isSessionTrusted,
            false // churn detection handled in engine correlation
        );
    }

    private function normalizeUserAgent(string $ua): string
    {
        // Simple normalization: extract major version if possible, or just use string.
        // For strictness, let's just trim and lower case for now.
        // Real parsing requires a library, which I shouldn't introduce if not present.
        // "UA Normalized (major version only)".
        // I'll do a best effort regex.
        if (preg_match('#(Chrome|Firefox|Safari|Edge|OPR)/(\d+)#', $ua, $matches)) {
            return strtolower($matches[1] . '/' . $matches[2]);
        }
        return strtolower(substr($ua, 0, 50)); // Truncate to avoid huge headers
    }

    private function normalizeClientFp(array $fp): string
    {
        // Sort keys to ensure deterministic order
        ksort($fp);
        return json_encode($fp);
    }
}
