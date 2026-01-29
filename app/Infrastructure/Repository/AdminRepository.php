<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Admin\Enum\AdminStatusEnum;
use App\Domain\DTO\AdminSessionIdentityDTO;
use PDO;

class AdminRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(string $displayName): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO admins (display_name, status, created_at)
         VALUES (?, ?, NOW())"
        );

        $stmt->execute([
            $displayName,
            AdminStatusEnum::ACTIVE->value,
        ]);

        return (int) $this->pdo->lastInsertId();
    }


    public function createFirstAdmin(): int
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
    /**
     * Returns the lifecycle status of the admin.
     * Fail-closed: missing row or invalid value will throw.
     */
    public function getStatus(int $adminId): AdminStatusEnum
    {
        $stmt = $this->pdo->prepare(
            "SELECT status FROM admins WHERE id = ?"
        );
        $stmt->execute([$adminId]);

        $value = $stmt->fetchColumn();

        if ($value === false) {
            throw new \RuntimeException('Admin not found when resolving status');
        }

        return AdminStatusEnum::from((string) $value);
    }



    public function getIdentitySnapshot(int $adminId): AdminSessionIdentityDTO
    {
        $stmt = $this->pdo->prepare("
            SELECT display_name, avatar_url
            FROM admins
            WHERE id = ?
        ");
        $stmt->execute([$adminId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            throw new \RuntimeException('Admin not found when resolving session identity');
        }

        $displayName = null;
        if (array_key_exists('display_name', $row) && is_string($row['display_name'])) {
            $displayName = trim($row['display_name']);
        }

        // Fail-closed display name snapshot: لا نسيبها فاضية
        if ($displayName === null || $displayName === '') {
            $displayName = 'Admin';
        }

        $avatarUrl = null;
        if (array_key_exists('avatar_url', $row) && is_string($row['avatar_url'])) {
            $candidate = trim($row['avatar_url']);
            if ($candidate !== '') {
                $avatarUrl = $candidate;
            }
        }

        return new AdminSessionIdentityDTO(
            displayName: $displayName,
            avatarUrl: $avatarUrl
        );
    }
}
