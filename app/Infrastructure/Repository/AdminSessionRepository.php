<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Contracts\AdminSessionRepositoryInterface;
use App\Domain\Contracts\AdminSessionValidationRepositoryInterface;
use App\Domain\Contracts\ClockInterface;
use App\Domain\DTO\AdminSessionIdentityDTO;
use PDO;

class AdminSessionRepository implements AdminSessionRepositoryInterface, AdminSessionValidationRepositoryInterface
{
    private PDO $pdo;
    private ClockInterface $clock;

    public function __construct(PDO $pdo, ClockInterface $clock)
    {
        $this->pdo = $pdo;
        $this->clock = $clock;
    }

    /* ===========================
     * Session Creation / Validation
     * =========================== */

    public function createSession(int $adminId): string
    {
        // Generate a secure random token
        $token = bin2hex(random_bytes(32));
        // Store HASH only (session_id column holds the hash)
        $tokenHash = hash('sha256', $token);
        $expiresAt = $this->clock->now()->modify('+2 hours')->format('Y-m-d H:i:s');

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

    /* ===========================
     * Pending TOTP Enrollment
     * =========================== */

    /**
     * @return array{
     *   seed_ciphertext: string,
     *   seed_iv: string,
     *   seed_tag: string,
     *   seed_key_id: string,
     *   issued_at: string
     * }|null
     */
    public function getPendingTotpEnrollmentByHash(string $sessionHash): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                pending_totp_seed_ciphertext,
                pending_totp_seed_iv,
                pending_totp_seed_tag,
                pending_totp_seed_key_id,
                pending_totp_issued_at
            FROM admin_sessions
            WHERE session_id = ?
              AND is_revoked = 0
        ");
        $stmt->execute([$sessionHash]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // ✅ phpstan requires explicit array check
        if (!is_array($row)) {
            return null;
        }

        if (!array_key_exists('pending_totp_seed_ciphertext', $row)) {
            return null;
        }

        if ($row['pending_totp_seed_ciphertext'] === null) {
            return null;
        }

        /** @var array{
         *   pending_totp_seed_ciphertext: string,
         *   pending_totp_seed_iv: string,
         *   pending_totp_seed_tag: string,
         *   pending_totp_seed_key_id: string,
         *   pending_totp_issued_at: string
         * } $typedRow
         */
        $typedRow = $row;


        return [
            'seed_ciphertext' => $typedRow['pending_totp_seed_ciphertext'],
            'seed_iv'         => $typedRow['pending_totp_seed_iv'],
            'seed_tag'        => $typedRow['pending_totp_seed_tag'],
            'seed_key_id'     => $typedRow['pending_totp_seed_key_id'],
            'issued_at'       => $typedRow['pending_totp_issued_at'],
        ];
    }

    public function storePendingTotpEnrollmentByHash(
        string $sessionHash,
        string $ciphertext,
        string $iv,
        string $tag,
        string $keyId,
        \DateTimeImmutable $issuedAt
    ): void {
        $stmt = $this->pdo->prepare("
            UPDATE admin_sessions
            SET
                pending_totp_seed_ciphertext = ?,
                pending_totp_seed_iv = ?,
                pending_totp_seed_tag = ?,
                pending_totp_seed_key_id = ?,
                pending_totp_issued_at = ?
            WHERE session_id = ?
              AND is_revoked = 0
        ");
        $stmt->execute([
            $ciphertext,
            $iv,
            $tag,
            $keyId,
            $issuedAt->format('Y-m-d H:i:s'),
            $sessionHash,
        ]);
    }

    public function clearPendingTotpEnrollmentByHash(string $sessionHash): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE admin_sessions
            SET
                pending_totp_seed_ciphertext = NULL,
                pending_totp_seed_iv = NULL,
                pending_totp_seed_tag = NULL,
                pending_totp_seed_key_id = NULL,
                pending_totp_issued_at = NULL
            WHERE session_id = ?
        ");
        $stmt->execute([$sessionHash]);
    }

    /* ===========================
     * Revocation (Hard Invalidation)
     * =========================== */

    public function revokeSession(string $token): void
    {
        $tokenHash = hash('sha256', $token);
        $this->revokeSessionByHash($tokenHash);
    }

    public function revokeSessionByHash(string $hash): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE admin_sessions
            SET
                is_revoked = 1,
                pending_totp_seed_ciphertext = NULL,
                pending_totp_seed_iv = NULL,
                pending_totp_seed_tag = NULL,
                pending_totp_seed_key_id = NULL,
                pending_totp_issued_at = NULL
            WHERE session_id = ?
        ");
        $stmt->execute([$hash]);
    }

    public function revokeAllSessions(int $adminId): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE admin_sessions
            SET
                is_revoked = 1,
                pending_totp_seed_ciphertext = NULL,
                pending_totp_seed_iv = NULL,
                pending_totp_seed_tag = NULL,
                pending_totp_seed_key_id = NULL,
                pending_totp_issued_at = NULL
            WHERE admin_id = ?
        ");
        $stmt->execute([$adminId]);
    }

    public function revokeSessionsByHash(array $hashes): void
    {
        if (empty($hashes)) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($hashes), '?'));

        $stmt = $this->pdo->prepare("
        UPDATE admin_sessions
        SET
            is_revoked = 1,
            pending_totp_seed_ciphertext = NULL,
            pending_totp_seed_iv = NULL,
            pending_totp_seed_tag = NULL,
            pending_totp_seed_key_id = NULL,
            pending_totp_issued_at = NULL
        WHERE session_id IN ($placeholders)
    ");

        $stmt->execute($hashes);
    }

    public function findAdminsBySessionHashes(array $hashes): array
    {
        if (empty($hashes)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($hashes), '?'));
        $stmt = $this->pdo->prepare("SELECT session_id, admin_id FROM admin_sessions WHERE session_id IN ($placeholders)");
        $stmt->execute($hashes);

        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * @return string[] session hashes (active + non-expired)
     */
    public function findActiveSessionHashesByAdmin(int $adminId): array
    {
        $stmt = $this->pdo->prepare("
        SELECT session_id
        FROM admin_sessions
        WHERE admin_id = :admin_id
          AND is_revoked = 0
          AND expires_at > NOW()
    ");

        $stmt->execute([
            'admin_id' => $adminId,
        ]);

        /** @var string[]|false $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if ($rows === false) {
            return [];
        }

        // Ensure strict string typing for phpstan
        return array_values(array_filter(
            $rows,
            static fn($v) => is_string($v) && $v !== ''
        ));
    }



    /* ===========================
     * Session Identity Snapshot
     * =========================== */

    public function storeSessionIdentityByHash(string $sessionHash, AdminSessionIdentityDTO $identity): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE admin_sessions
            SET
                display_name = ?,
                avatar_url = ?
            WHERE session_id = ?
        ");

        $stmt->execute([
            $identity->displayName,
            $identity->avatarUrl,
            $sessionHash,
        ]);
    }

    public function getSessionIdentityByHash(string $sessionHash): ?AdminSessionIdentityDTO
    {
        $stmt = $this->pdo->prepare("
            SELECT display_name, avatar_url
            FROM admin_sessions
            WHERE session_id = ?
              AND is_revoked = 0
        ");
        $stmt->execute([$sessionHash]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            return null;
        }

        if (!array_key_exists('display_name', $row) || !is_string($row['display_name'])) {
            return null;
        }

        $displayName = trim($row['display_name']);
        if ($displayName === '') {
            return null;
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
