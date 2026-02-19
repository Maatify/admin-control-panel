<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Contracts\Roles;

use Maatify\AdminKernel\Domain\DTO\Roles\RoleAdminsQueryResponseDTO;
use Maatify\AdminKernel\Domain\List\Filters\ResolvedListFilters;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;

interface RoleAdminsRepositoryInterface
{
    public function assign(int $roleId, int $adminId): void;

    public function unassign(int $roleId, int $adminId): void;

    public function queryAdminsForRole(
        int $roleId,
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): RoleAdminsQueryResponseDTO;
}
