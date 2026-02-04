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
 * DTO: AppSettingsQueryDTO
 *
 * Used for listing and searching application settings.
 * Intended for admin dashboards and internal APIs.
 */
final readonly class AppSettingsQueryDTO
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 50,
        public ?string $search = null,
        public ?string $group = null,
        public ?bool $isActive = true
    )
    {
    }
}
