<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Contracts\AdminDirectPermissionRepositoryInterface;
use App\Domain\Contracts\AdminRoleRepositoryInterface;
use App\Domain\Contracts\TelemetryAuditLoggerInterface;
use App\Context\RequestContext;
use App\Domain\Contracts\RolePermissionRepositoryInterface;
use App\Domain\Contracts\SecurityEventLoggerInterface;
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

        // NOTE: Kept intentionally for now.
        // TODO[AUDIT][BLOCKER]:
        // audit_outbox
        // TelemetryAuditLoggerInterface is currently injected but MUST NOT be used
        // for authorization decisions. It will be fully replaced by
        // NewAuthoritativeAuditLogger in a dedicated phase.
        private TelemetryAuditLoggerInterface $auditLogger,

        private SecurityEventLoggerInterface $securityLogger,
        private SystemOwnershipRepositoryInterface $systemOwnershipRepository
    ) {
    }

    public function checkPermission(int $adminId, string $permission, RequestContext $context): void
    {
        // 0. System Owner Bypass
        if ($this->systemOwnershipRepository->isOwner($adminId)) {
            // TODO[AUDIT][BLOCKER]:
            // audit_outbox
            // Authorization decision (system owner bypass) was previously logged
            // via TelemetryAuditLogger (best-effort, non-authoritative).
            // This MUST be replaced with NewAuthoritativeAuditLogger.
            // Original logger: TelemetryAuditLoggerInterface / PdoTelemetryAuditLogger.
            // Ref: Logging Architecture Audit – AuthorizationService::checkPermission
            return;
        }

        if (!$this->rolePermissionRepository->permissionExists($permission)) {
            $this->securityLogger->log(new SecurityEventDTO(
                $adminId,
                'permission_denied',
                'warning',
                ['reason' => 'unknown_permission', 'permission' => $permission],
                $context->ipAddress,
                $context->userAgent,
                new DateTimeImmutable(),
                $context->requestId
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
                        $context->ipAddress,
                        $context->userAgent,
                        new DateTimeImmutable(),
                        $context->requestId
                    ));
                    throw new PermissionDeniedException("Explicit deny for '$permission'.");
                }

                // TODO[AUDIT][BLOCKER]:
                // audit_outbox
                // Explicit permission allow was previously logged via TelemetryAuditLogger.
                // This is an AUTHORITY decision and MUST use Authoritative Audit Logging.
                // Ref: Logging Architecture Audit – AuthorizationService::checkPermission
                return;
            }
        }

        // 2. Role Permissions
        $roleIds = $this->adminRoleRepository->getRoleIds($adminId);

        if ($this->rolePermissionRepository->hasPermission($roleIds, $permission)) {
            // TODO[AUDIT][BLOCKER]:
            // audit_outbox
            // Role-based access grant was previously logged via TelemetryAuditLogger.
            // This MUST be replaced with NewAuthoritativeAuditLogger.
            // Ref: Logging Architecture Audit – AuthorizationService::checkPermission
            return;
        }

        // Default Deny
        $this->securityLogger->log(new SecurityEventDTO(
            $adminId,
            'permission_denied',
            'warning',
            ['reason' => 'missing_permission', 'permission' => $permission],
            $context->ipAddress,
            $context->userAgent,
            new DateTimeImmutable(),
            $context->requestId
        ));
        throw new PermissionDeniedException("Admin $adminId lacks permission '$permission'.");
    }

    public function hasPermission(int $adminId, string $permission): bool
    {
        // 0. System Owner Bypass
        if ($this->systemOwnershipRepository->isOwner($adminId)) {
            return true;
        }

        if (!$this->rolePermissionRepository->permissionExists($permission)) {
            return false;
        }

        // 1. Direct Permissions (Explicit Deny/Allow)
        $directPermissions = $this->directPermissionRepository->getActivePermissions($adminId);
        foreach ($directPermissions as $direct) {
            if ($direct['permission'] === $permission) {
                return (bool) $direct['is_allowed'];
            }
        }

        // 2. Role Permissions
        $roleIds = $this->adminRoleRepository->getRoleIds($adminId);

        return $this->rolePermissionRepository->hasPermission($roleIds, $permission);
    }
}
