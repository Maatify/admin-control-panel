<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Service;

use Maatify\AdminKernel\Context\RequestContext;
use Maatify\AdminKernel\Domain\Contracts\Admin\AdminDirectPermissionRepositoryInterface;
use Maatify\AdminKernel\Domain\Contracts\Admin\AdminRoleRepositoryInterface;
use Maatify\AdminKernel\Domain\Contracts\Permissions\PermissionMapperV2Interface;
use Maatify\AdminKernel\Domain\Contracts\Roles\RolePermissionRepositoryInterface;
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
//        private PermissionMapperInterface $permissionMapper,        // V1
        private PermissionMapperV2Interface $permissionMapperV2,    // V2
    ) {
    }

    /**
     * Authorization decision (throws on failure)
     */
    public function checkPermission(
        int $adminId,
        string $routeName,
        RequestContext $context
    ): void {
        // 0. System Owner Bypass
        // Authorization decision only — no audit, no activity
        if ($this->systemOwnershipRepository->isOwner($adminId)) {
            return;
        }

        $requirement = $this->permissionMapperV2->resolve($routeName);

        // AND logic
        if ($requirement->allOf !== []) {
            foreach ($requirement->allOf as $permission) {
                $this->assertSinglePermission($adminId, $permission);
            }
            return;
        }

        // OR logic
        if ($requirement->anyOf !== []) {
            foreach ($requirement->anyOf as $permission) {
                if ($this->hasSinglePermission($adminId, $permission)) {
                    return;
                }
            }

            throw new PermissionDeniedException(
                "Admin $adminId lacks required permissions (anyOf)."
            );
        }

        // Absolute fallback (defensive)
//        $permission = $this->permissionMapper->map($routeName);
//        $this->assertSinglePermission($adminId, $permission);
    }

    /**
     * Read-only helper — no logging
     */
    public function hasPermission(int $adminId, string $routeName): bool
    {
        if ($this->systemOwnershipRepository->isOwner($adminId)) {
            return true;
        }

        $requirement = $this->permissionMapperV2->resolve($routeName);

        // AND logic
        if ($requirement->allOf !== []) {
            foreach ($requirement->allOf as $permission) {
                if (!$this->hasSinglePermission($adminId, $permission)) {
                    return false;
                }
            }
            return true;
        }

        // OR logic: must have AT LEAST ONE permission
        if ($requirement->anyOf !== []) {
            foreach ($requirement->anyOf as $permission) {
                if ($this->hasSinglePermission($adminId, $permission)) {
                    return true;
                }
            }
            return false;
        }

        // Absolute fallback
//        $permission = $this->permissionMapper->map($routeName);
//        return $this->hasSinglePermission($adminId, $permission);

        /**
         * Defensive default:
         * - No requirements resolved
         * - Treat as denied (secure by default)
         */
        return false;
    }

    /**
     * Core single-permission assertion (throws)
     */
    private function assertSinglePermission(int $adminId, string $permission): void
    {
//        $permission = $this->permissionMapper->map($permission);

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

                // Explicit allow
                return;
            }
        }

        // 2. Role Permissions
        $roleIds = $this->adminRoleRepository->getRoleIds($adminId);

        if ($this->rolePermissionRepository->hasPermission($roleIds, $permission)) {
            return;
        }

        throw new PermissionDeniedException(
            "Admin $adminId lacks permission '$permission'."
        );
    }

    /**
     * Core single-permission check (boolean)
     */
    private function hasSinglePermission(int $adminId, string $permission): bool
    {
//        $permission = $this->permissionMapper->map($permission);

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
