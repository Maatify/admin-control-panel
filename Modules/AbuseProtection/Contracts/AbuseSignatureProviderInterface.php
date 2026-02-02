<?php

declare(strict_types=1);

namespace Maatify\AbuseProtection\Contracts;

/**
 * Provides cryptographic signing and verification for abuse signals.
 *
 * IMPORTANT:
 * - This interface MUST NOT assume any crypto implementation.
 * - No knowledge of keys, algorithms, rotation, or env.
 * - Implemented by the host application (Adapter).
 */
interface AbuseSignatureProviderInterface
{
    /**
     * Sign a payload and return a signature string.
     */
    public function sign(string $payload): string;

    /**
     * Verify a payload against a provided signature.
     */
    public function verify(string $payload, string $signature): bool;

    /**
     * Return the current key identifier (for rotation awareness).
     */
    public function currentKeyId(): string;
}
