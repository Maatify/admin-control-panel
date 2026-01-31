<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Contracts;

interface RolePermissionRepositoryInterface
{
    public function permissionExists(string $permissionName): bool;

    /**
     * @param int[] $roleIds
     * @param string $permissionName
     * @return bool
     */
    public function hasPermission(array $roleIds, string $permissionName): bool;
}
