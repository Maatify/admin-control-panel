<?php

declare(strict_types=1);

namespace App\Domain\Ownership;

interface SystemOwnershipRepositoryInterface
{
    /**
     * Check if system ownership has been assigned.
     */
    public function exists(): bool;

    /**
     * Check if the given admin is the system owner.
     */
    public function isOwner(int $adminId): bool;

    /**
     * Assign system ownership to an admin.
     * This must fail if ownership is already assigned.
     *
     * @param int $adminId
     * @throws \RuntimeException If ownership already exists.
     */
    public function assignOwner(int $adminId): void;
}
