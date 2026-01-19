<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Contracts\AdminPasswordRepositoryInterface;
use App\Domain\DTO\AdminPasswordRecordDTO;
use PDO;

class AdminPasswordRepository implements AdminPasswordRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function savePassword(
        int $adminId,
        string $passwordHash,
        string $pepperId,
        bool $mustChangePassword
    ): void {
        // Compatibility: Try UPDATE first, then INSERT if row count is 0.
        // This avoids ON DUPLICATE KEY UPDATE (MySQL specific) and works with SQLite.
        // Assuming transaction wraps this operation for atomicity.

        $stmt = $this->pdo->prepare("
            UPDATE admin_passwords
            SET password_hash = ?, pepper_id = ?, must_change_password = ?
            WHERE admin_id = ?
        ");

        $stmt->execute([
            $passwordHash,
            $pepperId,
            (int) $mustChangePassword,
            $adminId
        ]);

        if ($stmt->rowCount() === 0) {
            $stmt = $this->pdo->prepare("
                INSERT INTO admin_passwords (admin_id, password_hash, pepper_id, must_change_password)
                VALUES (?, ?, ?, ?)
            ");

            try {
                $stmt->execute([
                    $adminId,
                    $passwordHash,
                    $pepperId,
                    (int) $mustChangePassword
                ]);
            } catch (\PDOException $e) {
                // If race condition occurred and row was inserted by another process, ignore unique constraint violation
                // and retry update or assume it's fine.
                // In this specific repo context, we are mostly creating new admins or updating existing ones.
                // Re-throwing if it's not integrity constraint violation is safer.
                // Code 23000 is Integrity constraint violation.
                if ($e->getCode() !== '23000') {
                    throw $e;
                }

                // Fallback update for race condition
                 $stmt = $this->pdo->prepare("
                    UPDATE admin_passwords
                    SET password_hash = ?, pepper_id = ?, must_change_password = ?
                    WHERE admin_id = ?
                ");
                $stmt->execute([
                    $passwordHash,
                    $pepperId,
                    (int) $mustChangePassword,
                    $adminId
                ]);
            }
        }
    }

    public function getPasswordRecord(int $adminId): ?AdminPasswordRecordDTO
    {
        $stmt = $this->pdo->prepare("
        SELECT password_hash, pepper_id, must_change_password
        FROM admin_passwords
        WHERE admin_id = ?
    ");
        $stmt->execute([$adminId]);

        /** @var array{password_hash: string, pepper_id: string, must_change_password: int}|false $result */
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result === false) {
            return null;
        }

        return new AdminPasswordRecordDTO(
            hash: $result['password_hash'],
            pepperId: $result['pepper_id'],
            mustChangePassword: (bool) $result['must_change_password']
        );
    }
}
