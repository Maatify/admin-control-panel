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

namespace App\Infrastructure\Updater;

use App\Domain\Contracts\RolesMetadataRepositoryInterface;
use PDO;
use RuntimeException;

readonly class PDORolesMetadataRepository implements RolesMetadataRepositoryInterface
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function updateMetadata(int $roleId, ?string $displayName, ?string $description): void
    {
        // ─────────────────────────────
        // Guard: nothing to update
        // (controller / validation should prevent this, but we guard anyway)
        // ─────────────────────────────
        if ($displayName === null && $description === null) {
            throw new RuntimeException('Nothing to update for roles metadata.');
        }

        // ─────────────────────────────
        // Ensure Role exists
        // ─────────────────────────────
        $stmtExists = $this->pdo->prepare(
            'SELECT 1 FROM roles WHERE id = :id'
        );
        $stmtExists->execute(['id' => $roleId]);

        if ($stmtExists->fetchColumn() === false) {
            throw new RuntimeException("roles with id {$roleId} does not exist.");
        }

        // ─────────────────────────────
        // Build dynamic UPDATE
        // ─────────────────────────────
        $set    = [];
        $params = ['id' => $roleId];

        if ($displayName !== null) {
            $set[] = 'display_name = :display_name';
            $params['display_name'] = $displayName;
        }

        if ($description !== null) {
            $set[] = 'description = :description';
            $params['description'] = $description;
        }

        $sql = sprintf(
            'UPDATE roles SET %s WHERE id = :id',
            implode(', ', $set)
        );

        $stmt = $this->pdo->prepare($sql);

        if ($stmt->execute($params) === false) {
            throw new RuntimeException("Failed to update metadata for roles {$roleId}.");
        }
    }
}