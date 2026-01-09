<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-09 23:49
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Infrastructure\Query;

use App\Domain\List\ListCapabilities;
use App\Domain\List\ListQueryDTO;

final class ListFilterResolver
{
    public function resolve(
        ListQueryDTO $query,
        ListCapabilities $capabilities
    ): ResolvedListFilters
    {
        $globalSearch = null;

        if ($capabilities->supportsGlobalSearch && $query->globalSearch !== null) {
            $globalSearch = $query->globalSearch;
        }

        $columnFilters = [];

        if ($capabilities->supportsColumnFilters) {
            foreach ($query->columnFilters as $column => $value) {
                if (in_array($column, $capabilities->searchableColumns, true)) {
                    $columnFilters[$column] = $value;
                }
            }
        }

        $dateFrom = null;
        $dateTo = null;

        if ($capabilities->supportsDateFilter) {
            $dateFrom = $query->dateFrom;
            $dateTo = $query->dateTo;
        }

        return new ResolvedListFilters(
            $globalSearch,
            $columnFilters,
            $dateFrom,
            $dateTo
        );
    }
}
