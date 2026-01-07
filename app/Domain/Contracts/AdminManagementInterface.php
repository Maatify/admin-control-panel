<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\Admin\AdminCreateRequestDTO;
use App\Domain\DTO\Admin\AdminUpdateRequestDTO;

interface AdminManagementInterface
{
    public function createAdmin(AdminCreateRequestDTO $dto, int $actorId, string $actorSessionId): int;
    public function updateAdmin(int $adminId, AdminUpdateRequestDTO $dto, int $actorId, string $actorSessionId): void;
    public function disableAdmin(int $adminId, int $actorId): void;
}
