<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Contracts;

interface AdminSessionValidationRepositoryInterface
{
    /**
     * @return array{admin_id: int, expires_at: string, is_revoked: int}|null
     */
    public function findSession(string $token): ?array;

    public function revokeSession(string $token): void;

    public function revokeSessionByHash(string $hash): void;

    public function revokeAllSessions(int $adminId): void;

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
     * @return string[] session_hashes (ONLY active + non-expired)
     */
    public function findActiveSessionHashesByAdmin(int $adminId): array;

}
