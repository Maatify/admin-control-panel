<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Contracts\AdminRoleRepositoryInterface;
use App\Domain\Contracts\AuditLoggerInterface;
use App\Domain\Contracts\ClientInfoProviderInterface;
use App\Domain\Contracts\RolePermissionRepositoryInterface;
use App\Domain\Contracts\SecurityEventLoggerInterface;
use App\Domain\DTO\AuditEventDTO;
use App\Domain\DTO\SecurityEventDTO;
use App\Domain\Exception\PermissionDeniedException;
use App\Domain\Exception\UnauthorizedException;
use DateTimeImmutable;

readonly class AuthorizationService
{
    public function __construct(
        private AdminRoleRepositoryInterface $adminRoleRepository,
        private RolePermissionRepositoryInterface $rolePermissionRepository,
        private AuditLoggerInterface $auditLogger,
        private SecurityEventLoggerInterface $securityLogger,
        private ClientInfoProviderInterface $clientInfoProvider
    ) {
    }

    public function checkPermission(int $adminId, string $permission): void
    {
        if (!$this->rolePermissionRepository->permissionExists($permission)) {
            $this->securityLogger->log(new SecurityEventDTO(
                $adminId,
                'permission_denied',
                ['reason' => 'unknown_permission', 'permission' => $permission],
                $this->clientInfoProvider->getIpAddress(),
                $this->clientInfoProvider->getUserAgent(),
                new DateTimeImmutable()
            ));
            // "Unknown permission -> UnauthorizedException"
            throw new UnauthorizedException("Permission '$permission' does not exist.");
        }

        $roleIds = $this->adminRoleRepository->getRoleIds($adminId);

        if (!$this->rolePermissionRepository->hasPermission($roleIds, $permission)) {
            $this->securityLogger->log(new SecurityEventDTO(
                $adminId,
                'permission_denied',
                ['reason' => 'missing_permission', 'permission' => $permission],
                $this->clientInfoProvider->getIpAddress(),
                $this->clientInfoProvider->getUserAgent(),
                new DateTimeImmutable()
            ));
            // "Missing permission -> PermissionDeniedException"
            throw new PermissionDeniedException("Admin $adminId lacks permission '$permission'.");
        }

        $this->auditLogger->log(new AuditEventDTO(
            $adminId,
            'system_capability',
            null,
            'access_granted',
            ['permission' => $permission],
            $this->clientInfoProvider->getIpAddress(),
            $this->clientInfoProvider->getUserAgent(),
            new DateTimeImmutable()
        ));
    }
}
