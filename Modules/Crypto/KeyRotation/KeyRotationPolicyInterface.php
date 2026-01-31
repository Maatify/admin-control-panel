<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-08 10:32
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Crypto\KeyRotation;

use RuntimeException;

/**
 * KeyRotationPolicyInterface
 *
 * Defines how key rotation decisions are enforced.
 *
 * This interface:
 * - Enforces lifecycle rules
 * - Prevents invalid transitions
 * - Guarantees exactly one active key
 *
 * It does NOT:
 * - Store keys
 * - Perform crypto
 */
interface KeyRotationPolicyInterface
{
    /**
     * Validate provider state before usage.
     *
     * @throws RuntimeException if policy invariants are violated
     */
    public function validate(KeyProviderInterface $provider): void;

    /**
     * Resolve key for encryption.
     *
     * @throws RuntimeException if no active key is available
     */
    public function encryptionKey(KeyProviderInterface $provider): CryptoKeyInterface;

    /**
     * Resolve key for decryption by key_id.
     *
     * @throws RuntimeException if key is invalid or not allowed
     */
    public function decryptionKey(
        KeyProviderInterface $provider,
        string $keyId
    ): CryptoKeyInterface;
}
