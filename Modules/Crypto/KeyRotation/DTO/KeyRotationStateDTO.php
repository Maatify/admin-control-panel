<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-08 10:33
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Crypto\KeyRotation\DTO;

use Maatify\Crypto\KeyRotation\CryptoKeyInterface;

/**
 * KeyRotationStateDTO
 *
 * Snapshot of the key rotation state at a given point in time.
 *
 * Used for:
 * - Validation
 * - Auditing
 * - Debugging
 *
 * Contains NO logic.
 */
final readonly class KeyRotationStateDTO
{
    /**
     * @param   CryptoKeyInterface        $activeKey
     * @param   list<CryptoKeyInterface>  $inactiveKeys
     * @param   list<CryptoKeyInterface>  $retiredKeys
     */
    public function __construct(
        public CryptoKeyInterface $activeKey,
        public array $inactiveKeys,
        public array $retiredKeys
    )
    {
    }
}
