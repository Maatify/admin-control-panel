<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Contracts\AdminPasswordRepositoryInterface;
use PDO;

class AdminPasswordRepository implements AdminPasswordRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function savePassword(int $adminId, string $passwordHash): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO admin_passwords (admin_id, password_hash)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)
        ");
        $stmt->execute([$adminId, $passwordHash]);
    }

    public function getPasswordHash(int $adminId): ?string
    {
        $stmt = $this->pdo->prepare("SELECT password_hash FROM admin_passwords WHERE admin_id = ?");
        $stmt->execute([$adminId]);
        $result = $stmt->fetchColumn();

        return $result !== false ? (string)$result : null;
    }
}
