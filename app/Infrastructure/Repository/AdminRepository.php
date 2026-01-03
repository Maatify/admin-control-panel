<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use PDO;

class AdminRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO admins (created_at) VALUES (NOW())");
        $stmt->execute();

        return (int)$this->pdo->lastInsertId();
    }

    public function getCreatedAt(int $id): string
    {
        $stmt = $this->pdo->prepare("SELECT created_at FROM admins WHERE id = ?");
        $stmt->execute([$id]);
        
        return (string)$stmt->fetchColumn();
    }
}
