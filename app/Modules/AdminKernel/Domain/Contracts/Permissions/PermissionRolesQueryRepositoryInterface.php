<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Contracts\Permissions;

use Maatify\AdminKernel\Domain\DTO\Common\PaginationDTO;
use Maatify\AdminKernel\Domain\DTO\Permissions\PermissionRoleListItemDTO;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\AdminKernel\Infrastructure\Query\ResolvedListFilters;

interface PermissionRolesQueryRepositoryInterface
{
    /**
     * Query roles that use a specific permission
     *
     * - Read-only
     * - Paginated
     * - Filterable
     *
     * @return array{
     *   data: PermissionRoleListItemDTO[],
     *   pagination: PaginationDTO
     * }
     */
    public function queryRolesForPermission(
        int $permissionId,
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): array;
}
