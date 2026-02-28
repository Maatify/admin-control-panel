<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Contracts\Permissions;

use Maatify\AdminKernel\Domain\DTO\Permissions\DirectPermissionsQueryResponseDTO;
use Maatify\AdminKernel\Domain\List\Filters\ResolvedListFilters;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;

interface DirectPermissionsRepositoryInterface
{
    /**
     * Query direct permissions assigned explicitly to admin.
     *
     * - Admin-centric
     * - Direct permissions only (admin_direct_permissions)
     * - Read-only
     * - No RBAC resolution
     * - No role inference
     *
     * @throws \Maatify\AdminKernel\Domain\Exception\EntityNotFoundException
     */
    public function queryDirectPermissionsForAdmin(
        int $adminId,
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): DirectPermissionsQueryResponseDTO;
}
