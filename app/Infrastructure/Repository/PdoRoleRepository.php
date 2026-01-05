<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Contracts\RoleRepositoryInterface;
use PDO;

class PdoRoleRepository implements RoleRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getName(int $roleId): ?string
    {
        $stmt = $this->pdo->prepare('SELECT name FROM roles WHERE id = :id');
        $stmt->execute(['id' => $roleId]);
        $name = $stmt->fetchColumn();
        return $name === false ? null : (string)$name;
    }
}
