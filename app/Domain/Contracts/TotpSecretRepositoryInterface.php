<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

interface TotpSecretRepositoryInterface
{
    public function save(int $adminId, string $secret): void;

    public function get(int $adminId): ?string;
}
