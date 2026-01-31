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
 * KeyRotationValidationResultDTO
 *
 * Result object for validating key provider state.
 *
 * Explicit, readable, and audit-friendly.
 */
final readonly class KeyRotationValidationResultDTO
{
    public function __construct(
        public bool $isValid,
        public ?string $errorMessage = null
    )
    {
    }
}
