<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Contracts\Admin;

use Maatify\AdminKernel\Domain\DTO\AdminPasswordRecordDTO;

interface AdminPasswordRepositoryInterface
{
    public function savePassword(
        int $adminId,
        string $passwordHash,
        string $pepperId,
        bool $mustChangePassword
    ): void;

    public function getPasswordRecord(int $adminId): ?AdminPasswordRecordDTO;
}
