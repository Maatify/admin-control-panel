<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

interface AdminEmailPersistenceInterface
{
    public function addEmail(int $adminId, string $blindIndex, string $encryptedEmail): void;
}
