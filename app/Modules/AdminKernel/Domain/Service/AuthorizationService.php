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
    ) {
    }

    /**
     * Validates that the permission is canonical.
     */
    private function assertCanonical(string $permission): void
    {
        if (preg_match('/^.+\.(bulk|id|ui|api)$/', $permission)) {
            throw new \InvalidArgumentException("AuthorizationService requires canonical permission, rejected variant/transport: $permission");
        }
    }

    /**
     * Authorization decision (throws on failure)
     */
    public function checkPermission(
        int $adminId,
        string $permission,
        RequestContext $context
    ): void {
        $this->assertCanonical($permission);

        // 0. System Owner Bypass
        // Authorization decision only — no audit, no activity
        if ($this->systemOwnershipRepository->isOwner($adminId)) {
            return;
        }

        $this->assertSinglePermission($adminId, $permission);
    }

    /**
     * Read-only helper — no logging
     */
    public function hasPermission(int $adminId, string $permission): bool
    {
        $this->assertCanonical($permission);

        if ($this->systemOwnershipRepository->isOwner($adminId)) {
            return true;
        }

        return $this->hasSinglePermission($adminId, $permission);
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
