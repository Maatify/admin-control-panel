<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-09 23:48
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Domain\List;

final readonly class ListCapabilities
{
    /**
     * @param   string[]  $searchableColumns
     */
    public function __construct(
        public bool $supportsGlobalSearch,
        public array $searchableColumns,
        public bool $supportsColumnFilters,
        public bool $supportsDateFilter,
        public ?string $dateColumn,
    )
    {
    }
}
