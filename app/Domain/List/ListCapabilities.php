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
     * Capability contract for a LIST endpoint.
     *
     * Hard Rules:
     * - All capabilities are backend-owned. UI must not assume anything.
     * - Column filters MUST be declared explicitly (alias => sql_column).
     * - Unknown / undeclared filter aliases MUST be ignored or rejected by the resolver.
     * - Dynamic SQL columns are forbidden.
     * - Date filtering (if supported) applies to ONE predefined column only.
     *
     * @param string[] $searchableColumns
     *   List of allowed columns for global search.
     *   Each entry MUST be a trusted SQL column (e.g. "session_id", "id").
     *   Global search is applied as OR across these columns.
     *
     * @param array<string,string> $filterableColumns
     *   Map of allowed column filters.
     *   Key   = public alias sent by UI in "search.columns" (e.g. "id", "status").
     *   Value = trusted SQL column used by repository (e.g. "a.id", "s.is_revoked").
     *
     * @param ?string $dateColumn
     *   Trusted SQL column to apply date range to (e.g. "a.created_at").
     *   MUST be null when supportsDateFilter=false.
     *   MUST be non-null when supportsDateFilter=true.
     */
    public function __construct(
        public bool $supportsGlobalSearch,
        public array $searchableColumns,

        public bool $supportsColumnFilters,
        public array $filterableColumns,

        public bool $supportsDateFilter,
        public ?string $dateColumn,
    ) {
    }
}
