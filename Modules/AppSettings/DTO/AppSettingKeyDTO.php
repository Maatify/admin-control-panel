<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-04 20:28
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AppSettings\DTO;

/**
 * DTO: AppSettingKeyDTO
 *
 * Identifies a single application setting by (group, key).
 * Used for read, activate/deactivate, and protection checks.
 */
final readonly class AppSettingKeyDTO
{
    public function __construct(
        public string $group,
        public string $key
    )
    {
    }
}
