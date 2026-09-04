<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Contracts\Roles;

use Maatify\AdminKernel\Domain\DTO\Roles\RoleDetailsDTO;

interface RoleRepositoryInterface
{
    public function getName(int $roleId): ?string;

    public function getById(int $roleId): RoleDetailsDTO;
}
