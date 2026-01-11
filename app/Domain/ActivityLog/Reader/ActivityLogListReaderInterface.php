<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-11 22:19
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Domain\ActivityLog\Reader;

use App\Domain\DTO\ActivityLog\ActivityLogListResponseDTO;
use App\Domain\List\ListQueryDTO;
use App\Infrastructure\Query\ResolvedListFilters;

interface ActivityLogListReaderInterface
{
    /**
     * Fetch activity logs list using canonical list query.
     *
     * HARD RULES:
     * - Pagination, search, filters, and date handling are applied by the Reader.
     * - Filters passed here are already resolved & validated by ListFilterResolver.
     * - Authorization MUST be enforced by the caller (Controller).
     * - No mutation, no side effects.
     *
     * @param   ListQueryDTO         $query
     *   Canonical list query DTO (page, per_page, search, date).
     *
     * @param   ResolvedListFilters  $filters
     *   Resolved filters produced by ListFilterResolver.
     *   Keys are trusted SQL column aliases defined in ListCapabilities.
     *
     * @return ActivityLogListResponseDTO
     */
    public function getActivityLogs(
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): ActivityLogListResponseDTO;
}
