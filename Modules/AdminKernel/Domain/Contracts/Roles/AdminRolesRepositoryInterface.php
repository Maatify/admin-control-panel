<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-01 19:54
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Contracts\Roles;

use Maatify\AdminKernel\Domain\DTO\Roles\AdminRolesQueryResponseDTO;
use Maatify\AdminKernel\Domain\List\Filters\ResolvedListFilters;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;

interface AdminRolesRepositoryInterface
{
    /**
     * Query roles assigned to a specific admin.
     *
     * - Admin-centric
     * - Assigned roles only
     * - Read-only (inspection)
     *
     * @throws \Maatify\AdminKernel\Domain\Exception\EntityNotFoundException
     */
    public function queryRolesForAdmin(
        int $adminId,
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): AdminRolesQueryResponseDTO;
}
