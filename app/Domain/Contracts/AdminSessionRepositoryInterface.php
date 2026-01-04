<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

interface AdminSessionRepositoryInterface
{
    public function createSession(int $adminId): string;

    public function invalidateSession(string $token): void;

    public function getAdminIdFromSession(string $token): ?int;
}
