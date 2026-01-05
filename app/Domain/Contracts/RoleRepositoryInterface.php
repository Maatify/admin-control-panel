<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

interface RoleRepositoryInterface
{
    public function getName(int $roleId): ?string;
}
