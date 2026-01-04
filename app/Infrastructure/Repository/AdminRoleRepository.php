<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Contracts\AdminRoleRepositoryInterface;
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
        $stmt = $this->pdo->prepare('SELECT role_id FROM admin_roles WHERE admin_id = :admin_id');
        $stmt->execute(['admin_id' => $adminId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }
}
