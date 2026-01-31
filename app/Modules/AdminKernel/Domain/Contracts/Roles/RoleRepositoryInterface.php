<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Contracts\Roles;

interface RoleRepositoryInterface
{
    public function getName(int $roleId): ?string;
}
