<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-08 10:34
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Crypto\KeyRotation\DTO;

/**
 * KeyRotationDecisionDTO
 *
 * Represents the outcome of a rotation decision.
 *
 * POLICY OUTPUT ONLY — does NOT mutate anything.
 */
final readonly class KeyRotationDecisionDTO
{
    /**
     * @param   string  $newActiveKeyId
     * @param   string  $previousActiveKeyId
     * @param   bool    $rotationOccurred
     */
    public function __construct(
        public string $newActiveKeyId,
        public string $previousActiveKeyId,
        public bool $rotationOccurred
    )
    {
    }
}
