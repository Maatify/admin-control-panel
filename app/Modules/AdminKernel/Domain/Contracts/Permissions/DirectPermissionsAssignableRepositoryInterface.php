<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Contracts\Permissions;

use Maatify\AdminKernel\Domain\DTO\Permissions\DirectPermissionsAssignableQueryResponseDTO;
use Maatify\AdminKernel\Domain\List\Filters\ResolvedListFilters;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;

interface DirectPermissionsAssignableRepositoryInterface
{
    public function queryAssignablePermissionsForAdmin(
        int $adminId,
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): DirectPermissionsAssignableQueryResponseDTO;
}
