<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Contracts\AdminSessionRepositoryInterface;
use PDO;

class AdminSessionRepository implements AdminSessionRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function createSession(int $adminId): string
    {
        // Generate a secure random token
        $token = bin2hex(random_bytes(32));
        $expiresAt = (new \DateTimeImmutable('+2 hours'))->format('Y-m-d H:i:s');

        $stmt = $this->pdo->prepare("
            INSERT INTO admin_sessions (session_id, admin_id, expires_at)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$token, $adminId, $expiresAt]);

        return $token;
    }

    public function invalidateSession(string $token): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM admin_sessions WHERE session_id = ?");
        $stmt->execute([$token]);
    }

    public function getAdminIdFromSession(string $token): ?int
    {
        $stmt = $this->pdo->prepare("
            SELECT admin_id
            FROM admin_sessions
            WHERE session_id = ? AND expires_at > NOW()
        ");
        $stmt->execute([$token]);
        $result = $stmt->fetchColumn();

        return $result !== false ? (int)$result : null;
    }
}
