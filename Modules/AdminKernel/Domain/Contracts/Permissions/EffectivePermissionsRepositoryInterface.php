<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Contracts\Permissions;

use Maatify\AdminKernel\Domain\DTO\Permissions\EffectivePermissionsQueryResponseDTO;
use Maatify\AdminKernel\Domain\List\Filters\ResolvedListFilters;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;

interface EffectivePermissionsRepositoryInterface
{
    /**
     * Query effective permissions snapshot for admin.
     *
     * - Read-only
     * - Applies full RBAC precedence
     * - Role + Direct permissions
     *
     * @throws \Maatify\AdminKernel\Domain\Exception\EntityNotFoundException
     */
    public function queryEffectivePermissionsForAdmin(
        int $adminId,
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): EffectivePermissionsQueryResponseDTO;
}
