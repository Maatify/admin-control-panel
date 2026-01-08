<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-08 09:59
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\Crypto\Reversible\DTO;

/**
 * ReversibleCryptoMetadata
 *
 * Metadata required to successfully decrypt reversible encrypted data.
 *
 * This object MUST match the algorithm requirements:
 * - IV must be present if required
 * - Tag must be present if required
 *
 * Validation of requirements is the responsibility of the algorithm implementation.
 */
final readonly class ReversibleCryptoMetadataDTO
{
    /**
     * @param   string|null  $iv   Initialization Vector
     * @param   string|null  $tag  Authentication Tag
     */
    public function __construct(
        public ?string $iv,
        public ?string $tag
    )
    {
    }
}
