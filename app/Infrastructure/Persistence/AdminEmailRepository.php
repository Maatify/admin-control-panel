<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use PDO;

class AdminEmailRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function add(int $adminId, string $blindIndex, string $encryptedEmail): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO admin_emails (admin_id, email_blind_index, email_encrypted) VALUES (?, ?, ?)");
        $stmt->execute([$adminId, $blindIndex, $encryptedEmail]);
    }

    public function findByBlindIndex(string $blindIndex): ?int
    {
        $stmt = $this->pdo->prepare("SELECT admin_id FROM admin_emails WHERE email_blind_index = ?");
        $stmt->execute([$blindIndex]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result && is_array($result)) {
            return (int)$result['admin_id'];
        }
        return null;
    }

    public function getEncryptedEmail(int $adminId): ?string
    {
        $stmt = $this->pdo->prepare("SELECT email_encrypted FROM admin_emails WHERE admin_id = ?");
        $stmt->execute([$adminId]);
        $result = $stmt->fetchColumn();

        return $result === false ? null : (string)$result;
    }
}
