<?php
/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-26 00:39
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Domain\Contracts;

interface PermissionsMetadataRepositoryInterface
{
    /**
     * Update UI metadata for a single permission.
     *
     * - Does NOT modify permission technical key (name)
     * - Does NOT affect roles or admin permissions
     * - UI support only
     *
     * @param int         $permissionId
     * @param string|null $displayName
     * @param string|null $description
     *
     * @throws \RuntimeException if permission does not exist
     */
    public function updateMetadata(
        int $permissionId,
        ?string $displayName,
        ?string $description
    ): void;
}