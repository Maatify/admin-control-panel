<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Contracts;

interface AdminDirectPermissionRepositoryInterface
{
    /**
     * @return array<array{permission: string, is_allowed: bool}>
     */
    public function getActivePermissions(int $adminId): array;
}
