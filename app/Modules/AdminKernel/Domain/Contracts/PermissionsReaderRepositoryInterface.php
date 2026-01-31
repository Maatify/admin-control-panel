<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-25 18:54
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Contracts;

use Maatify\AdminKernel\Domain\DTO\Permission\PermissionsQueryResponseDTO;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\AdminKernel\Infrastructure\Query\ResolvedListFilters;

interface PermissionsReaderRepositoryInterface
{

    /**
     * Canonical Permission Query endpoint.
     *
     * @param   ListQueryDTO         $query
     * @param   ResolvedListFilters  $filters
     *
     * @return PermissionsQueryResponseDTO
     */
    public function queryPermissions(
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): PermissionsQueryResponseDTO;
}
