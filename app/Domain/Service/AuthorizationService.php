<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Contracts\AdminRoleRepositoryInterface;
use App\Domain\Contracts\AuditLoggerInterface;
use App\Domain\Contracts\ClientInfoProviderInterface;
use App\Domain\Contracts\TransactionalAuditWriterInterface;
use App\Domain\Contracts\RolePermissionRepositoryInterface;
use App\Domain\Contracts\SecurityEventLoggerInterface;
use App\Domain\DTO\AuditEventDTO;
use App\Domain\DTO\LegacyAuditEventDTO;
use App\Domain\DTO\SecurityEventDTO;
use App\Domain\Exception\PermissionDeniedException;
use App\Domain\Exception\UnauthorizedException;
use DateTimeImmutable;
use PDO;

readonly class AuthorizationService
{
    public function __construct(
        private AdminRoleRepositoryInterface $adminRoleRepository,
        private RolePermissionRepositoryInterface $rolePermissionRepository,
        private AuditLoggerInterface $auditLogger,
        private SecurityEventLoggerInterface $securityLogger,
        private ClientInfoProviderInterface $clientInfoProvider,
        private TransactionalAuditWriterInterface $outboxWriter,
        private PDO $pdo
    ) {
    }

    public function checkPermission(int $adminId, string $permission): void
    {
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
            // "Unknown permission -> UnauthorizedException"
            throw new UnauthorizedException("Permission '$permission' does not exist.");
        }

        $roleIds = $this->adminRoleRepository->getRoleIds($adminId);

        if (!$this->rolePermissionRepository->hasPermission($roleIds, $permission)) {
            $this->securityLogger->log(new SecurityEventDTO(
                $adminId,
                'permission_denied',
                'warning',
                ['reason' => 'missing_permission', 'permission' => $permission],
                $this->clientInfoProvider->getIpAddress(),
                $this->clientInfoProvider->getUserAgent(),
                new DateTimeImmutable()
            ));
            // "Missing permission -> PermissionDeniedException"
            throw new PermissionDeniedException("Admin $adminId lacks permission '$permission'.");
        }

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
    }

    public function assignRole(int $adminId, int $roleId): void
    {
        $this->pdo->beginTransaction();
        try {
            $this->adminRoleRepository->assign($adminId, $roleId);

            $this->outboxWriter->write(new AuditEventDTO(
                $adminId,
                'role_assigned',
                'admin',
                $adminId,
                'HIGH',
                ['role_id' => $roleId],
                bin2hex(random_bytes(16)),
                new DateTimeImmutable()
            ));

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
