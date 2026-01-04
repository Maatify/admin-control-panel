<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Enum\VerificationStatus;
use PDO;
use RuntimeException;

class AdminEmailRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function addEmail(int $adminId, string $blindIndex, string $encryptedEmail): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO admin_emails (admin_id, email_blind_index, email_encrypted) VALUES (?, ?, ?)");
        $stmt->execute([$adminId, $blindIndex, $encryptedEmail]);
    }

    public function findByBlindIndex(string $blindIndex): ?int
    {
        $stmt = $this->pdo->prepare("SELECT admin_id FROM admin_emails WHERE email_blind_index = ?");
        $stmt->execute([$blindIndex]);
        $result = $stmt->fetchColumn();

        return $result !== false ? (int)$result : null;
    }

    public function getEncryptedEmail(int $adminId): ?string
    {
        $stmt = $this->pdo->prepare("SELECT email_encrypted FROM admin_emails WHERE admin_id = ?");
        $stmt->execute([$adminId]);
        $result = $stmt->fetchColumn();

        return $result !== false ? (string)$result : null;
    }

    public function getVerificationStatus(int $adminId): VerificationStatus
    {
        $stmt = $this->pdo->prepare("SELECT verification_status FROM admin_emails WHERE admin_id = ?");
        $stmt->execute([$adminId]);
        $result = $stmt->fetchColumn();

        if ($result === false) {
            throw new RuntimeException("Admin email not found for ID: $adminId");
        }

        return VerificationStatus::from((string)$result);
    }

    public function markVerified(int $adminId, string $timestamp): void
    {
        $stmt = $this->pdo->prepare("UPDATE admin_emails SET verification_status = ?, verified_at = ? WHERE admin_id = ?");
        $stmt->execute([VerificationStatus::VERIFIED->value, $timestamp, $adminId]);
    }

    public function markFailed(int $adminId): void
    {
        $stmt = $this->pdo->prepare("UPDATE admin_emails SET verification_status = ?, verified_at = NULL WHERE admin_id = ?");
        $stmt->execute([VerificationStatus::FAILED->value, $adminId]);
    }

    public function markPending(int $adminId): void
    {
        $stmt = $this->pdo->prepare("UPDATE admin_emails SET verification_status = ?, verified_at = NULL WHERE admin_id = ?");
        $stmt->execute([VerificationStatus::PENDING->value, $adminId]);
    }
}
