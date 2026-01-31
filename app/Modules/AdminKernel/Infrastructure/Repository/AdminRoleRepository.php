<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Repository;

use Maatify\AdminKernel\Domain\Contracts\AdminRoleRepositoryInterface;
use PDO;

class AdminRoleRepository implements AdminRoleRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getRoleIds(int $adminId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT ar.role_id
         FROM admin_roles ar
         INNER JOIN roles r ON r.id = ar.role_id
         WHERE ar.admin_id = :admin_id
           AND r.is_active = 1'
        );

        $stmt->execute(['admin_id' => $adminId]);

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function assign(int $adminId, int $roleId): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO admin_roles (admin_id, role_id) VALUES (:admin_id, :role_id)');
        $stmt->execute(['admin_id' => $adminId, 'role_id' => $roleId]);
    }
}
