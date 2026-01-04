<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Contracts\RolePermissionRepositoryInterface;
use PDO;

class RolePermissionRepository implements RolePermissionRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function permissionExists(string $permissionName): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM permissions WHERE name = :name');
        $stmt->execute(['name' => $permissionName]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function hasPermission(array $roleIds, string $permissionName): bool
    {
        if (empty($roleIds)) {
            return false;
        }

        // Create placeholders for IN clause
        $placeholders = implode(',', array_fill(0, count($roleIds), '?'));

        $sql = "
            SELECT COUNT(*)
            FROM role_permissions rp
            JOIN permissions p ON rp.permission_id = p.id
            WHERE p.name = ? AND rp.role_id IN ($placeholders)
        ";

        $params = array_merge([$permissionName], $roleIds);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn() > 0;
    }
}
