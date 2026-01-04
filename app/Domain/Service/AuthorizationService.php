<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Contracts\AdminRoleRepositoryInterface;
use App\Domain\Contracts\RolePermissionRepositoryInterface;
use App\Domain\Exception\PermissionDeniedException;
use App\Domain\Exception\UnauthorizedException;

readonly class AuthorizationService
{
    public function __construct(
        private AdminRoleRepositoryInterface $adminRoleRepository,
        private RolePermissionRepositoryInterface $rolePermissionRepository
    ) {
    }

    public function checkPermission(int $adminId, string $permission): void
    {
        if (!$this->rolePermissionRepository->permissionExists($permission)) {
            // "Unknown permission -> UnauthorizedException"
            throw new UnauthorizedException("Permission '$permission' does not exist.");
        }

        $roleIds = $this->adminRoleRepository->getRoleIds($adminId);

        if (!$this->rolePermissionRepository->hasPermission($roleIds, $permission)) {
            // "Missing permission -> PermissionDeniedException"
            throw new PermissionDeniedException("Admin $adminId lacks permission '$permission'.");
        }
    }
}
