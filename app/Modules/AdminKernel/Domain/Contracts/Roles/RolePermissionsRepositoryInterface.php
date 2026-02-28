<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Contracts\Roles;

use Maatify\AdminKernel\Domain\DTO\Roles\RolePermissionsQueryResponseDTO;
use Maatify\AdminKernel\Domain\List\Filters\ResolvedListFilters;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;

interface RolePermissionsRepositoryInterface
{
    /**
     * Canonical paginated query for role permissions
     */
    public function queryForRole(
        int $roleId,
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): RolePermissionsQueryResponseDTO;

    /**
     * Assign permission to role (idempotent)
     */
    public function assign(int $roleId, int $permissionId): void;

    /**
     * Unassign permission from role (idempotent)
     */
    public function unassign(int $roleId, int $permissionId): void;
}
