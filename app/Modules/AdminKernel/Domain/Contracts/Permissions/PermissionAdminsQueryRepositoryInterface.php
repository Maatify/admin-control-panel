<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Contracts\Permissions;

use Maatify\AdminKernel\Domain\DTO\Common\PaginationDTO;
use Maatify\AdminKernel\Domain\DTO\Permissions\PermissionAdminOverrideListItemDTO;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\AdminKernel\Infrastructure\Query\ResolvedListFilters;

interface PermissionAdminsQueryRepositoryInterface
{
    /**
     * Query admins that have direct overrides for a permission
     *
     * - Read-only
     * - Paginated
     * - Filterable
     *
     * @return array{
     *   data: PermissionAdminOverrideListItemDTO[],
     *   pagination: PaginationDTO
     * }
     */
    public function queryAdminsForPermission(
        int $permissionId,
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): array;
}
