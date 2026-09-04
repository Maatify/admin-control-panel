<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-27 00:04
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Repository\Roles;

use Maatify\AdminKernel\Domain\Contracts\Roles\RoleCreateRepositoryInterface;
use Maatify\AdminKernel\Domain\Exception\EntityAlreadyExistsException;
use PDO;
use RuntimeException;

readonly class PdoRoleCreateRepository implements RoleCreateRepositoryInterface
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(
        string $name,
        ?string $displayName,
        ?string $description
    ): void {
        // ─────────────────────────────
        // Guard: unique technical name
        // ─────────────────────────────
        $stmtCheck = $this->pdo->prepare(
            'SELECT 1 FROM roles WHERE name = :name'
        );
        $stmtCheck->execute(['name' => $name]);

        if ($stmtCheck->fetchColumn() !== false) {
            throw new EntityAlreadyExistsException(
                entity: 'Role',
                field: 'name',
                value: $name
            );
        }

        // ─────────────────────────────
        // Insert role
        // ─────────────────────────────
        $stmt = $this->pdo->prepare(
            'INSERT INTO roles (name, display_name, description, is_active)
             VALUES (:name, :display_name, :description, 1)'
        );

        if ($stmt->execute([
                'name'         => $name,
                'display_name' => $displayName,
                'description'  => $description,
            ]) === false) {
            throw new RuntimeException("Failed to create role '{$name}'.");
        }
    }
}
