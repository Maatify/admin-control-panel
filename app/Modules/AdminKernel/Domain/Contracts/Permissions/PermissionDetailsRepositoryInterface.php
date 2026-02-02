<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Contracts\Permissions;

use Maatify\AdminKernel\Domain\DTO\Permissions\PermissionDetailsDTO;

interface PermissionDetailsRepositoryInterface
{
    /**
     * Permission overview
     */
    public function getPermissionById(int $permissionId): PermissionDetailsDTO;
}
