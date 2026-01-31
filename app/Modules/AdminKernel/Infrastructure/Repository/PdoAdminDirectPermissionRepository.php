<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Repository;

use Maatify\AdminKernel\Domain\Contracts\AdminDirectPermissionRepositoryInterface;
use PDO;

class PdoAdminDirectPermissionRepository implements AdminDirectPermissionRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getActivePermissions(int $adminId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT p.name, adp.is_allowed
            FROM admin_direct_permissions adp
            JOIN permissions p ON p.id = adp.permission_id
            WHERE adp.admin_id = ?
            AND (adp.expires_at IS NULL OR adp.expires_at > NOW())
        ");
        $stmt->execute([$adminId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function ($row) {
            return [
                'permission' => (string)$row['name'],
                'is_allowed' => (bool)$row['is_allowed'],
            ];
        }, $results);
    }
}
