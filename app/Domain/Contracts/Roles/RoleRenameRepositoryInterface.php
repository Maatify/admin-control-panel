<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-26 23:44
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Domain\Contracts\Roles;

interface RoleRenameRepositoryInterface
{
    /**
     * Rename role technical key.
     *
     * ⚠️ High-impact operation:
     * - Does NOT touch permissions
     * - Does NOT touch admin-role bindings
     * - Updates roles.name only
     *
     * @throws \RuntimeException if role not found or name already exists
     */
    public function rename(int $roleId, string $newName): void;
}
