<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-10-01 00:00
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\I18n\Reader;

use Maatify\AdminKernel\Domain\DTO\Common\PaginationDTO;
use Maatify\AdminKernel\Domain\List\Filters\ResolvedListFilters;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\Persistence\Pdo\Pagination\PageRequest;
use Maatify\Persistence\Pdo\Pagination\PageResult;

/**
 * Host adaptation between the Admin list contract (ListQueryDTO /
 * ResolvedListFilters / PaginationDTO) and the I18n package's public
 * Criteria + maatify/persistence PageResult.
 *
 * Host list semantics only: no SQL, no pagination mechanics.
 */
final class PackageListMapper
{
    public static function page(ListQueryDTO $query): PageRequest
    {
        return new PageRequest($query->page, $query->perPage);
    }

    /**
     * @template TItem of array<array-key, mixed>|object
     *
     * @param PageResult<TItem> $result
     */
    public static function pagination(PageResult $result): PaginationDTO
    {
        return new PaginationDTO(
            page: $result->page,
            perPage: $result->perPage,
            total: $result->total,
            filtered: $result->filtered
        );
    }

    public static function int(ResolvedListFilters $filters, string $alias): ?int
    {
        return array_key_exists($alias, $filters->columnFilters)
            ? (int) $filters->columnFilters[$alias]
            : null;
    }

    public static function string(ResolvedListFilters $filters, string $alias): ?string
    {
        return array_key_exists($alias, $filters->columnFilters)
            ? trim($filters->columnFilters[$alias])
            : null;
    }

    /**
     * `1` => true, `0` => false, anything else / absent => no filter.
     */
    public static function flag(ResolvedListFilters $filters, string $alias): ?bool
    {
        $value = self::int($filters, $alias);

        return match ($value) {
            1 => true,
            0 => false,
            default => null,
        };
    }
}
