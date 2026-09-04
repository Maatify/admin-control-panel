<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Contracts\Permissions;

interface DirectPermissionsWriterRepositoryInterface
{
    /**
     * Assign or update direct permission for admin.
     *
     * - Upsert behavior
     * - Overwrites previous value
     */
    public function assignDirectPermission(
        int $adminId,
        int $permissionId,
        bool $isAllowed,
        ?string $expiresAt
    ): void;

    /**
     * Revoke (remove) direct permission from admin.
     *
     * - Hard delete
     * - No history
     * - No audit (handled elsewhere if needed)
     */
    public function revokeDirectPermission(
        int $adminId,
        int $permissionId
    ): void;
}
