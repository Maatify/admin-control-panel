<?php
/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-26 20:24
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\Roles\RolesQueryResponseDTO;
use App\Domain\List\ListQueryDTO;
use App\Infrastructure\Query\ResolvedListFilters;

interface RolesReaderRepositoryInterface
{

    /**
     * Canonical Roles Query endpoint.
     *
     * @param   ListQueryDTO         $query
     * @param   ResolvedListFilters  $filters
     *
     * @return RolesQueryResponseDTO
     */
    public function queryRoles(
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): RolesQueryResponseDTO;
}