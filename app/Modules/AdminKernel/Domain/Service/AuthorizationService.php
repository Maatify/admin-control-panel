<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Service;

use Maatify\AdminKernel\Domain\Contracts\AdminDirectPermissionRepositoryInterface;
use Maatify\AdminKernel\Domain\Contracts\AdminRoleRepositoryInterface;
use Maatify\AdminKernel\Context\RequestContext;
use Maatify\AdminKernel\Domain\Contracts\PermissionMapperInterface;
use Maatify\AdminKernel\Domain\Contracts\RolePermissionRepositoryInterface;
use Maatify\AdminKernel\Domain\Exception\PermissionDeniedException;
use Maatify\AdminKernel\Domain\Exception\UnauthorizedException;
use Maatify\AdminKernel\Domain\Ownership\SystemOwnershipRepositoryInterface;

readonly class AuthorizationService
{
    public function __construct(
        private AdminRoleRepositoryInterface $adminRoleRepository,
        private RolePermissionRepositoryInterface $rolePermissionRepository,
        private AdminDirectPermissionRepositoryInterface $directPermissionRepository,
        private SystemOwnershipRepositoryInterface $systemOwnershipRepository,
        private PermissionMapperInterface $permissionMapper,
    ) {
    }

    public function checkPermission(int $adminId, string $permission, RequestContext $context): void
    {
        // 0. System Owner Bypass
        // Authorization decision only — no audit, no activity
        if ($this->systemOwnershipRepository->isOwner($adminId)) {
            return;
        }

        $permission = $this->permissionMapper->map($permission);

        if (!$this->rolePermissionRepository->permissionExists($permission)) {
            throw new UnauthorizedException("Permission '$permission' does not exist.");
        }

        // 1. Direct Permissions (Explicit Deny/Allow)
        $directPermissions = $this->directPermissionRepository->getActivePermissions($adminId);
        foreach ($directPermissions as $direct) {
            if ($direct['permission'] === $permission) {
                if (!$direct['is_allowed']) {
                    throw new PermissionDeniedException("Explicit deny for '$permission'.");
                }

                // Explicit allow — authorization decision only
                return;
            }
        }

        // 2. Role Permissions
        $roleIds = $this->adminRoleRepository->getRoleIds($adminId);

        if ($this->rolePermissionRepository->hasPermission($roleIds, $permission)) {
            // Role-based allow — authorization decision only
            return;
        }

        throw new PermissionDeniedException("Admin $adminId lacks permission '$permission'.");
    }

    public function hasPermission(int $adminId, string $permission): bool
    {
        // Read-only helper — no logging
        if ($this->systemOwnershipRepository->isOwner($adminId)) {
            return true;
        }

        $permission = $this->permissionMapper->map($permission);

        if (!$this->rolePermissionRepository->permissionExists($permission)) {
            return false;
        }

        $directPermissions = $this->directPermissionRepository->getActivePermissions($adminId);
        foreach ($directPermissions as $direct) {
            if ($direct['permission'] === $permission) {
                return (bool) $direct['is_allowed'];
            }
        }

        $roleIds = $this->adminRoleRepository->getRoleIds($adminId);

        return $this->rolePermissionRepository->hasPermission($roleIds, $permission);
    }
}
