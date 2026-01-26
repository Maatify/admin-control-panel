<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Contracts\AdminDirectPermissionRepositoryInterface;
use App\Domain\Contracts\AdminRoleRepositoryInterface;
use App\Context\RequestContext;
use App\Domain\Contracts\RolePermissionRepositoryInterface;
use App\Domain\Exception\PermissionDeniedException;
use App\Domain\SecurityEvents\DTO\SecurityEventRecordDTO;
use App\Domain\SecurityEvents\Enum\SecurityEventActorTypeEnum;
use App\Domain\SecurityEvents\Recorder\SecurityEventRecorderInterface;
use App\Modules\SecurityEvents\Enum\SecurityEventSeverityEnum;
use App\Modules\SecurityEvents\Enum\SecurityEventTypeEnum;
use App\Domain\Exception\UnauthorizedException;
use App\Domain\Ownership\SystemOwnershipRepositoryInterface;
use DateTimeImmutable;

readonly class AuthorizationService
{
    public function __construct(
        private AdminRoleRepositoryInterface $adminRoleRepository,
        private RolePermissionRepositoryInterface $rolePermissionRepository,
        private AdminDirectPermissionRepositoryInterface $directPermissionRepository,
        private SecurityEventRecorderInterface $securityLogger,
        private SystemOwnershipRepositoryInterface $systemOwnershipRepository
    ) {
    }

    public function checkPermission(int $adminId, string $permission, RequestContext $context): void
    {
        // 0. System Owner Bypass
        // Authorization decision only — no audit, no activity
        if ($this->systemOwnershipRepository->isOwner($adminId)) {
            return;
        }

        if (!$this->rolePermissionRepository->permissionExists($permission)) {
            $this->securityLogger->record(new SecurityEventRecordDTO(
                SecurityEventActorTypeEnum::ADMIN,
                $adminId,
                SecurityEventTypeEnum::PERMISSION_DENIED,
                SecurityEventSeverityEnum::WARNING,
                $context->requestId,
                null,
                $context->ipAddress,
                $context->userAgent,
                ['reason' => 'unknown_permission', 'permission' => $permission]
            ));
            throw new UnauthorizedException("Permission '$permission' does not exist.");
        }

        // 1. Direct Permissions (Explicit Deny/Allow)
        $directPermissions = $this->directPermissionRepository->getActivePermissions($adminId);
        foreach ($directPermissions as $direct) {
            if ($direct['permission'] === $permission) {
                if (!$direct['is_allowed']) {
                    $this->securityLogger->record(new SecurityEventRecordDTO(
                        SecurityEventActorTypeEnum::ADMIN,
                        $adminId,
                        SecurityEventTypeEnum::PERMISSION_DENIED,
                        SecurityEventSeverityEnum::WARNING,
                        $context->requestId,
                        null,
                        $context->ipAddress,
                        $context->userAgent,
                        ['reason' => 'explicit_deny', 'permission' => $permission]
                    ));
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

        // Default Deny
        $this->securityLogger->record(new SecurityEventRecordDTO(
            SecurityEventActorTypeEnum::ADMIN,
            $adminId,
            SecurityEventTypeEnum::PERMISSION_DENIED,
            SecurityEventSeverityEnum::WARNING,
            $context->requestId,
            null,
            $context->ipAddress,
            $context->userAgent,
            ['reason' => 'missing_permission', 'permission' => $permission]
        ));

        throw new PermissionDeniedException("Admin $adminId lacks permission '$permission'.");
    }

    public function hasPermission(int $adminId, string $permission): bool
    {
        // Read-only helper — no logging
        if ($this->systemOwnershipRepository->isOwner($adminId)) {
            return true;
        }

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
