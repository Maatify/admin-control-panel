<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-08 09:56
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\Crypto\Reversible;

/**
 * ReversibleCryptoAlgorithmEnum
 *
 * Security whitelist for reversible (decryptable) symmetric cryptography algorithms.
 *
 * IMPORTANT:
 * - This enum describes ALLOWED algorithms only.
 * - It does NOT implement encryption or decryption.
 * - It MUST NOT be used for hashing or one-way secrets.
 *
 * Any algorithm added here is considered a SECURITY DECISION.
 */
enum ReversibleCryptoAlgorithmEnum: string
{
    /**
     * AES-256-GCM
     * AEAD cipher with authentication tag.
     * Default and recommended algorithm.
     */
    case AES_256_GCM = 'aes-256-gcm';

    /**
     * AES-128-GCM
     * AEAD cipher with authentication tag.
     * Allowed but less preferred than AES-256-GCM.
     */
    case AES_128_GCM = 'aes-128-gcm';

    /**
     * ChaCha20-Poly1305
     * AEAD cipher suitable for environments without AES acceleration.
     */
    case CHACHA20_POLY1305 = 'chacha20-poly1305';

    /**
     * AES-256-CBC
     * NOT AEAD.
     * Requires IV but provides no authentication tag.
     * Allowed only for legacy or restricted use cases.
     */
    case AES_256_CBC = 'aes-256-cbc';

    /**
     * Whether this algorithm requires an initialization vector (IV).
     */
    public function requiresIv(): bool
    {
        return true;
    }

    /**
     * Whether this algorithm produces / requires an authentication tag.
     */
    public function requiresTag(): bool
    {
        return match ($this) {
            self::AES_256_CBC => false,
            default => true,
        };
    }

    /**
     * Whether this algorithm is AEAD (Authenticated Encryption with Associated Data).
     */
    public function isAead(): bool
    {
        return $this !== self::AES_256_CBC;
    }

    /**
     * Indicates whether this algorithm is considered safe for general-purpose usage.
     */
    public function isRecommended(): bool
    {
        return match ($this) {
            self::AES_256_GCM,
            self::CHACHA20_POLY1305 => true,
            default => false,
        };
    }
}
