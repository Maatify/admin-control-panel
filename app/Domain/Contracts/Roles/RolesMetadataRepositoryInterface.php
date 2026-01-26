<?php
/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-26 20:41
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Domain\Contracts\Roles;

interface RolesMetadataRepositoryInterface
{
    /**
     * Update UI metadata for a single role.
     *
     * - Does NOT modify role technical key (name)
     * - Does NOT affect roles or admin role
     * - UI support only
     *
     * @param int          $roleId
     * @param string|null  $displayName
     * @param string|null  $description
     *
     * @throws \RuntimeException if role does not exist
     */
    public function updateMetadata(
        int $roleId,
        ?string $displayName,
        ?string $description
    ): void;
}