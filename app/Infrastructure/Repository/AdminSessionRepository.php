<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Contracts\AdminSessionRepositoryInterface;
use App\Domain\Contracts\AdminSessionValidationRepositoryInterface;
use PDO;

class AdminSessionRepository implements AdminSessionRepositoryInterface, AdminSessionValidationRepositoryInterface
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
            INSERT INTO admin_sessions (session_id, admin_id, expires_at, is_revoked)
            VALUES (?, ?, ?, 0)
        ");
        $stmt->execute([$token, $adminId, $expiresAt]);

        return $token;
    }

    public function invalidateSession(string $token): void
    {
        $this->revokeSession($token);
    }

    public function getAdminIdFromSession(string $token): ?int
    {
        // Maintains backward compatibility with Phase 4, but checks revoked status too
        $stmt = $this->pdo->prepare("
            SELECT admin_id
            FROM admin_sessions
            WHERE session_id = ? AND expires_at > NOW() AND is_revoked = 0
        ");
        $stmt->execute([$token]);
        $result = $stmt->fetchColumn();

        return $result !== false ? (int)$result : null;
    }

    /**
     * @return array{admin_id: int, expires_at: string, is_revoked: int}|null
     */
    public function findSession(string $token): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT admin_id, expires_at, is_revoked
            FROM admin_sessions
            WHERE session_id = ?
        ");
        $stmt->execute([$token]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result === false) {
            return null;
        }

        /** @var array{admin_id: string, expires_at: string, is_revoked: string} $result */
        return [
            'admin_id' => (int) $result['admin_id'],
            'expires_at' => $result['expires_at'],
            'is_revoked' => (int) $result['is_revoked'],
        ];

    }

    public function revokeSession(string $token): void
    {
        $stmt = $this->pdo->prepare("UPDATE admin_sessions SET is_revoked = 1 WHERE session_id = ?");
        $stmt->execute([$token]);
    }

    public function revokeAllSessions(int $adminId): void
    {
        $stmt = $this->pdo->prepare("UPDATE admin_sessions SET is_revoked = 1 WHERE admin_id = ?");
        $stmt->execute([$adminId]);
    }
}
