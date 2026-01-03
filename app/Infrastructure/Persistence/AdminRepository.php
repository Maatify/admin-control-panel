<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use PDO;

class AdminRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @return array{admin_id: int, created_at: mixed}
     */
    public function create(): array
    {
        $stmt = $this->pdo->prepare("INSERT INTO admins (created_at) VALUES (NOW())");
        $stmt->execute();

        $id = (int)$this->pdo->lastInsertId();

        $stmt = $this->pdo->prepare("SELECT created_at FROM admins WHERE id = ?");
        $stmt->execute([$id]);
        $createdAt = $stmt->fetchColumn();

        return [
            'admin_id' => $id,
            'created_at' => $createdAt
        ];
    }
}
