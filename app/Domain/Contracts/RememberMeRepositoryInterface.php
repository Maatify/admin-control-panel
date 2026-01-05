<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\RememberMeTokenDTO;

interface RememberMeRepositoryInterface
{
    public function save(RememberMeTokenDTO $token): void;
    public function findBySelector(string $selector): ?RememberMeTokenDTO;
    public function deleteBySelector(string $selector): void;
    public function deleteByAdminId(int $adminId): void;
}
