<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Repository;

use Maatify\AdminKernel\Domain\Contracts\Admin\AdminPasswordRepositoryInterface;
use Maatify\AdminKernel\Domain\DTO\AdminPasswordRecordDTO;
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
        $stmt = $this->pdo->prepare("
        INSERT INTO admin_passwords (admin_id, password_hash, pepper_id, must_change_password)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            password_hash = VALUES(password_hash),
            pepper_id = VALUES(pepper_id),
            must_change_password = VALUES(must_change_password)
    ");

        $stmt->execute([
            $adminId,
            $passwordHash,
            $pepperId,
            (int) $mustChangePassword
        ]);
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
