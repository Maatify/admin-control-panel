<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-27 00:03
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Contracts\Roles;

interface RoleCreateRepositoryInterface
{
    /**
     * Create a new role.
     *
     * Rules:
     * - name is a technical immutable key after creation
     * - role is created as is_active = 1
     * - no permissions assigned
     * - no admins assigned
     *
     * @throws \RuntimeException on duplicate name or failure
     */
    public function create(
        string $name,
        ?string $displayName,
        ?string $description
    ): void;
}
