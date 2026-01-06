<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Contracts\AdminDirectPermissionRepositoryInterface;
use App\Domain\Contracts\AdminRoleRepositoryInterface;
use App\Domain\Contracts\TelemetryAuditLoggerInterface;
use App\Domain\Contracts\ClientInfoProviderInterface;
use App\Domain\Contracts\RolePermissionRepositoryInterface;
use App\Domain\Contracts\SecurityEventLoggerInterface;
use App\Domain\DTO\LegacyAuditEventDTO;
use App\Domain\DTO\SecurityEventDTO;
use App\Domain\Exception\PermissionDeniedException;
use App\Domain\Exception\UnauthorizedException;
use App\Domain\Ownership\SystemOwnershipRepositoryInterface;
use DateTimeImmutable;

readonly class AuthorizationService
{
    public function __construct(
        private AdminRoleRepositoryInterface $adminRoleRepository,
        private RolePermissionRepositoryInterface $rolePermissionRepository,
        private AdminDirectPermissionRepositoryInterface $directPermissionRepository,
        private TelemetryAuditLoggerInterface $auditLogger,
        private SecurityEventLoggerInterface $securityLogger,
        private ClientInfoProviderInterface $clientInfoProvider,
        private SystemOwnershipRepositoryInterface $systemOwnershipRepository
    ) {
    }

    public function checkPermission(int $adminId, string $permission): void
    {
        // 0. System Owner Bypass
        if ($this->systemOwnershipRepository->isOwner($adminId)) {
            // Log access grant for audit purposes, but bypass all checks
            $this->auditLogger->log(new LegacyAuditEventDTO(
                $adminId,
                'system_capability',
                null,
                'access_granted',
                ['permission' => $permission, 'source' => 'system_owner'],
                $this->clientInfoProvider->getIpAddress(),
                $this->clientInfoProvider->getUserAgent(),
                new DateTimeImmutable()
            ));
            return;
        }

        if (!$this->rolePermissionRepository->permissionExists($permission)) {
            $this->securityLogger->log(new SecurityEventDTO(
                $adminId,
                'permission_denied',
                'warning',
                ['reason' => 'unknown_permission', 'permission' => $permission],
                $this->clientInfoProvider->getIpAddress(),
                $this->clientInfoProvider->getUserAgent(),
                new DateTimeImmutable()
            ));
            throw new UnauthorizedException("Permission '$permission' does not exist.");
        }

        // 1. Direct Permissions (Explicit Deny/Allow)
        $directPermissions = $this->directPermissionRepository->getActivePermissions($adminId);
        foreach ($directPermissions as $direct) {
            if ($direct['permission'] === $permission) {
                if (!$direct['is_allowed']) {
                    $this->securityLogger->log(new SecurityEventDTO(
                        $adminId,
                        'permission_denied',
                        'warning',
                        ['reason' => 'explicit_deny', 'permission' => $permission],
                        $this->clientInfoProvider->getIpAddress(),
                        $this->clientInfoProvider->getUserAgent(),
                        new DateTimeImmutable()
                    ));
                    throw new PermissionDeniedException("Explicit deny for '$permission'.");
                }

                // Explicit Allow
                $this->auditLogger->log(new LegacyAuditEventDTO(
                    $adminId,
                    'system_capability',
                    null,
                    'access_granted',
                    ['permission' => $permission, 'source' => 'direct'],
                    $this->clientInfoProvider->getIpAddress(),
                    $this->clientInfoProvider->getUserAgent(),
                    new DateTimeImmutable()
                ));
                return;
            }
        }

        // 2. Role Permissions
        $roleIds = $this->adminRoleRepository->getRoleIds($adminId);

        if ($this->rolePermissionRepository->hasPermission($roleIds, $permission)) {
            $this->auditLogger->log(new LegacyAuditEventDTO(
                $adminId,
                'system_capability',
                null,
                'access_granted',
                ['permission' => $permission],
                $this->clientInfoProvider->getIpAddress(),
                $this->clientInfoProvider->getUserAgent(),
                new DateTimeImmutable()
            ));
            return;
        }

        // Default Deny
        $this->securityLogger->log(new SecurityEventDTO(
            $adminId,
            'permission_denied',
            'warning',
            ['reason' => 'missing_permission', 'permission' => $permission],
            $this->clientInfoProvider->getIpAddress(),
            $this->clientInfoProvider->getUserAgent(),
            new DateTimeImmutable()
        ));
        throw new PermissionDeniedException("Admin $adminId lacks permission '$permission'.");
    }
}
