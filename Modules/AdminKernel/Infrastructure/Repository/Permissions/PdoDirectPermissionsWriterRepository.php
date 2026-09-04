<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Repository\Permissions;

use Maatify\AdminKernel\Domain\Contracts\Permissions\DirectPermissionsWriterRepositoryInterface;
use Maatify\AdminKernel\Domain\Exception\EntityNotFoundException;
use PDO;
use RuntimeException;

final readonly class PdoDirectPermissionsWriterRepository implements DirectPermissionsWriterRepositoryInterface
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function assignDirectPermission(
        int $adminId,
        int $permissionId,
        bool $isAllowed,
        ?string $expiresAt
    ): void {

        // Ensure admin exists
        $stmt = $this->pdo->prepare('SELECT 1 FROM admins WHERE id = :id');
        $stmt->execute(['id' => $adminId]);

        if ($stmt->fetchColumn() === false) {
            throw new EntityNotFoundException('admin', $adminId);
        }

        // Ensure permission exists
        $stmt = $this->pdo->prepare('SELECT 1 FROM permissions WHERE id = :id');
        $stmt->execute(['id' => $permissionId]);

        if ($stmt->fetchColumn() === false) {
            throw new EntityNotFoundException('permission', $permissionId);
        }

        $sql = '
            INSERT INTO admin_direct_permissions (
                admin_id,
                permission_id,
                is_allowed,
                expires_at
            ) VALUES (
                :admin_id,
                :permission_id,
                :is_allowed,
                :expires_at
            )
            ON DUPLICATE KEY UPDATE
                is_allowed = VALUES(is_allowed),
                expires_at = VALUES(expires_at),
                granted_at = CURRENT_TIMESTAMP
        ';

        $stmt = $this->pdo->prepare($sql);

        $ok = $stmt->execute([
            'admin_id'      => $adminId,
            'permission_id' => $permissionId,
            'is_allowed'    => $isAllowed ? 1 : 0,
            'expires_at'    => $expiresAt,
        ]);

        if ($ok === false) {
            throw new RuntimeException('Failed to assign direct permission');
        }
    }

    public function revokeDirectPermission(
        int $adminId,
        int $permissionId
    ): void {
        $stmt = $this->pdo->prepare(
            '
            DELETE FROM admin_direct_permissions
            WHERE admin_id = :admin_id
              AND permission_id = :permission_id
            '
        );

        if ($stmt->execute([
                'admin_id'      => $adminId,
                'permission_id' => $permissionId,
            ]) === false) {
            throw new RuntimeException('Failed to revoke direct permission');
        }
    }
}
