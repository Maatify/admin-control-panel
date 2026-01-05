<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

interface AdminRoleRepositoryInterface
{
    /**
     * @param int $adminId
     * @return int[]
     */
    public function getRoleIds(int $adminId): array;

    public function assign(int $adminId, int $roleId): void;
}
