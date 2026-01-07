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
        // Store HASH only (session_id column holds the hash)
        $tokenHash = hash('sha256', $token);
        $expiresAt = (new \DateTimeImmutable('+2 hours'))->format('Y-m-d H:i:s');

        $stmt = $this->pdo->prepare("
            INSERT INTO admin_sessions (session_id, admin_id, expires_at, is_revoked)
            VALUES (?, ?, ?, 0)
        ");
        // We use $tokenHash as the session_id
        $stmt->execute([$tokenHash, $adminId, $expiresAt]);

        return $token;
    }

    public function invalidateSession(string $token): void
    {
        $this->revokeSession($token);
    }

    public function getAdminIdFromSession(string $token): ?int
    {
        $tokenHash = hash('sha256', $token);
        // Maintains backward compatibility with Phase 4, but checks revoked status too
        $stmt = $this->pdo->prepare("
            SELECT admin_id
            FROM admin_sessions
            WHERE session_id = ? AND expires_at > NOW() AND is_revoked = 0
        ");
        $stmt->execute([$tokenHash]);
        $result = $stmt->fetchColumn();

        return $result !== false ? (int)$result : null;
    }

    /**
     * @return array{admin_id: int, expires_at: string, is_revoked: int}|null
     */
    public function findSession(string $token): ?array
    {
        $tokenHash = hash('sha256', $token);
        return $this->findSessionByHash($tokenHash);
    }

    /**
     * @return array{admin_id: int, expires_at: string, is_revoked: int}|null
     */
    public function findSessionByHash(string $hash): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT admin_id, expires_at, is_revoked
            FROM admin_sessions
            WHERE session_id = ?
        ");
        $stmt->execute([$hash]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result === false) {
            return null;
        }

        /** @var array{admin_id: string|int, expires_at: string, is_revoked: string|int} $result */
        return [
            'admin_id' => (int) $result['admin_id'],
            'expires_at' => $result['expires_at'],
            'is_revoked' => (int) $result['is_revoked'],
        ];
    }

    public function revokeSession(string $token): void
    {
        $tokenHash = hash('sha256', $token);
        $this->revokeSessionByHash($tokenHash);
    }

    public function revokeSessionByHash(string $hash): void
    {
        $stmt = $this->pdo->prepare("UPDATE admin_sessions SET is_revoked = 1 WHERE session_id = ?");
        $stmt->execute([$hash]);
    }

    public function revokeAllSessions(int $adminId): void
    {
        $stmt = $this->pdo->prepare("UPDATE admin_sessions SET is_revoked = 1 WHERE admin_id = ?");
        $stmt->execute([$adminId]);
    }
}
