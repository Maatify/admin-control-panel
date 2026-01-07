<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

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
}
