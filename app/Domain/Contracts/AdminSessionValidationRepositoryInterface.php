<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

interface AdminSessionValidationRepositoryInterface
{
    /**
     * @return array{admin_id: int, expires_at: string, is_revoked: int}|null
     */
    public function findSession(string $token): ?array;

    public function revokeSession(string $token): void;

    public function revokeAllSessions(int $adminId): void;
}
