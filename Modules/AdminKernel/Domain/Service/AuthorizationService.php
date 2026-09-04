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
     * @return array<string>
     */
    private function getCandidatePermissions(string $required): array
    {
        $candidates = [$required];

        if (str_ends_with($required, '.query')) {
            $prefix = substr($required, 0, -6);
            array_push(
                $candidates,
                $prefix . '.create',
                $prefix . '.update',
                $prefix . '.activate',
                $prefix . '.deactivate',
                $prefix . '.archive',
                $prefix . '.publish'
            );
        } elseif (str_ends_with($required, '.view')) {
            $prefix = substr($required, 0, -5);
            $candidates[] = $prefix . '.edit';
        }

        return $candidates;
    }

    /**
     * Checks if the user is granted at least one of the candidate permissions.
     *
     * @param array<string> $candidates
     */
    private function isGrantedAnyCandidate(int $adminId, array $candidates): bool
    {
        $directPermissions = $this->directPermissionRepository->getActivePermissions($adminId);
        $roleIds = $this->adminRoleRepository->getRoleIds($adminId);

        // 1. Global Explicit Deny Check (Precedence)
        foreach ($candidates as $candidate) {
            if (!$this->rolePermissionRepository->permissionExists($candidate)) {
                continue;
            }

            foreach ($directPermissions as $direct) {
                if ($direct['permission'] === $candidate && !(bool) $direct['is_allowed']) {
                    return false; // Explicit deny on ANY valid candidate blocks immediately
                }
            }
        }

        // 2. Allow Check
        foreach ($candidates as $candidate) {
            if (!$this->rolePermissionRepository->permissionExists($candidate)) {
                continue;
            }

            foreach ($directPermissions as $direct) {
                if ($direct['permission'] === $candidate && (bool) $direct['is_allowed']) {
                    return true;
                }
            }

            if ($this->rolePermissionRepository->hasPermission($roleIds, $candidate)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Core single-permission assertion (throws)
     */
    private function assertSinglePermission(int $adminId, string $permission): void
    {
        if (!$this->rolePermissionRepository->permissionExists($permission)) {
            throw new PermissionDeniedException("Permission '$permission' does not exist.");
        }

        $candidates = $this->getCandidatePermissions($permission);

        if ($this->isGrantedAnyCandidate($adminId, $candidates)) {
            return;
        }

        // Direct Permissions (Explicit Deny) for original permission
        $directPermissions = $this->directPermissionRepository->getActivePermissions($adminId);
        foreach ($directPermissions as $direct) {
            if ($direct['permission'] === $permission) {
                if (!$direct['is_allowed']) {
                    throw new PermissionDeniedException("Explicit deny for '$permission'.");
                }
            }
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
        if (!$this->rolePermissionRepository->permissionExists($permission)) {
            return false;
        }

        $candidates = $this->getCandidatePermissions($permission);
        return $this->isGrantedAnyCandidate($adminId, $candidates);
    }
}
