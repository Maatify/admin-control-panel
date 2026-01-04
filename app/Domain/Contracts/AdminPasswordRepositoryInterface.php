<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

interface AdminPasswordRepositoryInterface
{
    public function savePassword(int $adminId, string $passwordHash): void;

    public function getPasswordHash(int $adminId): ?string;
}
