<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-08 10:30
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Crypto\KeyRotation;

/**
 * KeyStatusEnum
 *
 * Defines the lifecycle state of a cryptographic key.
 *
 * IMPORTANT:
 * - This enum is POLICY ONLY.
 * - It has NO cryptographic meaning.
 */
enum KeyStatusEnum: string
{
    /**
     * Active key.
     * - Used for encryption
     * - Used for decryption
     */
    case ACTIVE = 'active';

    /**
     * Inactive key.
     * - NOT used for encryption
     * - Allowed for decryption
     */
    case INACTIVE = 'inactive';

    /**
     * Retired key.
     * - NOT used for encryption
     * - Allowed for decryption (legacy only)
     */
    case RETIRED = 'retired';

    /**
     * Whether encryption is allowed with this key.
     */
    public function canEncrypt(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Whether decryption is allowed with this key.
     */
    public function canDecrypt(): bool
    {
        return true;
    }
}
