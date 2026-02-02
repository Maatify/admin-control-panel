<?php

declare(strict_types=1);

namespace Maatify\AbuseProtection\Domain\Service;

use Maatify\AbuseProtection\Contracts\AbuseSignatureProviderInterface;
use Maatify\AbuseProtection\DTO\AbuseSignalDTO;
use Maatify\SharedCommon\Contracts\ClockInterface;

/**
 * Abuse Protection core service.
 *
 * - Stateless
 * - Pure domain logic
 * - No framework knowledge
 * - No crypto assumptions
 *
 * Fully compatible with SharedCommon ClockInterface.
 */
final readonly class AbuseProtectionService
{
    public function __construct(
        private AbuseSignatureProviderInterface $signer,
        private ClockInterface $clock,
        private int $ttlSeconds = 300 // default: 5 minutes
    ) {}

    /**
     * Issue a new abuse signal.
     */
    public function issueSignal(
        int $failureCount,
        string $clientFingerprint
    ): AbuseSignalDTO {
        $expiresAt = $this->clock
                         ->now()
                         ->getTimestamp() + $this->ttlSeconds;

        $payload = $this->buildPayload(
            $failureCount,
            $expiresAt,
            $clientFingerprint
        );

        $signature = $this->signer->sign($payload);

        return new AbuseSignalDTO(
            failureCount: $failureCount,
            expiresAt: $expiresAt,
            keyId: $this->signer->currentKeyId(),
            signature: $signature
        );
    }

    /**
     * Validate an incoming abuse signal.
     */
    public function validateSignal(
        AbuseSignalDTO $signal,
        string $clientFingerprint
    ): bool {
        $now = $this->clock->now()->getTimestamp();

        if ($signal->expiresAt < $now) {
            return false;
        }

        $payload = $this->buildPayload(
            $signal->failureCount,
            $signal->expiresAt,
            $clientFingerprint
        );

        return $this->signer->verify($payload, $signal->signature);
    }

    /**
     * Increment failure count and re-issue signal.
     */
    public function incrementFailure(
        AbuseSignalDTO $signal,
        string $clientFingerprint
    ): AbuseSignalDTO {
        return $this->issueSignal(
            $signal->failureCount + 1,
            $clientFingerprint
        );
    }

    /**
     * Build the canonical payload to be signed.
     *
     * IMPORTANT:
     * - Order MUST NOT change.
     * - Payload structure is cryptographically bound by the host adapter.
     */
    private function buildPayload(
        int $failureCount,
        int $expiresAt,
        string $clientFingerprint
    ): string {
        return implode('|', [
            $failureCount,
            $expiresAt,
            $clientFingerprint,
        ]);
    }
}
