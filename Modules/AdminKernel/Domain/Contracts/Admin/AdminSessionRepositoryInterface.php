<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Contracts\Admin;

use Maatify\AdminKernel\Domain\DTO\AdminSessionIdentityDTO;

interface AdminSessionRepositoryInterface
{
    public function createSession(int $adminId): string;

    public function invalidateSession(string $token): void;

    public function revokeSession(string $token): void;

    public function revokeSessionByHash(string $hash): void;

    public function getAdminIdFromSession(string $token): ?int;

    /**
     * @return array{admin_id: int, expires_at: string, is_revoked: int}|null
     */
    public function findSessionByHash(string $hash): ?array;

    /**
     * @param string[] $hashes
     */
    public function revokeSessionsByHash(array $hashes): void;

    /**
     * @param string[] $hashes
     * @return array<string, int> Map of session_hash => admin_id
     */
    public function findAdminsBySessionHashes(array $hashes): array;

    /**
     * @return array{
     *   seed_ciphertext: string,
     *   seed_iv: string,
     *   seed_tag: string,
     *   seed_key_id: string,
     *   issued_at: string
     * }|null
     */
    public function getPendingTotpEnrollmentByHash(string $sessionHash): ?array;

    public function storePendingTotpEnrollmentByHash(
        string $sessionHash,
        string $ciphertext,
        string $iv,
        string $tag,
        string $keyId,
        \DateTimeImmutable $issuedAt
    ): void;

    public function clearPendingTotpEnrollmentByHash(string $sessionHash): void;

    // ---------------------------
    // Session Identity Snapshot
    // ---------------------------
    public function storeSessionIdentityByHash(string $sessionHash, AdminSessionIdentityDTO $identity): void;

    public function getSessionIdentityByHash(string $sessionHash): ?AdminSessionIdentityDTO;
}
