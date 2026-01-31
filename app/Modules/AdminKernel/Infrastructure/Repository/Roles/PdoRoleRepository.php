<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Repository\Roles;

use Maatify\AdminKernel\Domain\Contracts\Roles\RoleRenameRepositoryInterface;
use Maatify\AdminKernel\Domain\Contracts\Roles\RoleRepositoryInterface;
use Maatify\AdminKernel\Domain\Contracts\Roles\RolesMetadataRepositoryInterface;
use Maatify\AdminKernel\Domain\Contracts\Roles\RoleToggleRepositoryInterface;
use Maatify\AdminKernel\Domain\Exception\EntityAlreadyExistsException;
use Maatify\AdminKernel\Domain\Exception\EntityNotFoundException;
use Maatify\AdminKernel\Domain\Exception\InvalidOperationException;
use PDO;
use RuntimeException;

class PdoRoleRepository implements RoleRepositoryInterface,
                                   RolesMetadataRepositoryInterface,
                                   RoleToggleRepositoryInterface,
                                   RoleRenameRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getName(int $roleId): ?string
    {
        $stmt = $this->pdo->prepare('SELECT name FROM roles WHERE id = :id');
        $stmt->execute(['id' => $roleId]);
        $name = $stmt->fetchColumn();
        return $name === false ? null : (string)$name;
    }

    public function updateMetadata(int $roleId, ?string $displayName, ?string $description): void
    {
        // ─────────────────────────────
        // Guard: nothing to update
        // (controller / validation should prevent this, but we guard anyway)
        // ─────────────────────────────
        if ($displayName === null && $description === null) {
            throw new InvalidOperationException(
                'Role',
                'update_metadata',
                'no updatable fields provided'
            );
        }

        // ─────────────────────────────
        // Ensure Role exists
        // ─────────────────────────────
        $stmtExists = $this->pdo->prepare(
            'SELECT 1 FROM roles WHERE id = :id'
        );
        $stmtExists->execute(['id' => $roleId]);

        if ($stmtExists->fetchColumn() === false) {
            throw new EntityNotFoundException('role', $roleId);
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

    public function toggle(int $roleId, bool $isActive): void
    {
        // ─────────────────────────────
        // Ensure role exists
        // ─────────────────────────────
        $stmtExists = $this->pdo->prepare(
            'SELECT 1 FROM roles WHERE id = :id'
        );
        $stmtExists->execute(['id' => $roleId]);

        if ($stmtExists->fetchColumn() === false) {
            throw new EntityNotFoundException('role', $roleId);
        }

        // ─────────────────────────────
        // Update activation state
        // ─────────────────────────────
        $stmt = $this->pdo->prepare(
            'UPDATE roles SET is_active = :is_active WHERE id = :id'
        );

        if ($stmt->execute([
                'id' => $roleId,
                'is_active' => $isActive ? 1 : 0
            ]) === false) {
            throw new RuntimeException("Failed to toggle role {$roleId}.");
        }
    }

    public function rename(int $roleId, string $newName): void
    {
        // ─────────────────────────────
        // Ensure role exists
        // ─────────────────────────────
        $stmtExists = $this->pdo->prepare(
            'SELECT name FROM roles WHERE id = :id'
        );
        $stmtExists->execute(['id' => $roleId]);

        $currentName = $stmtExists->fetchColumn();

        if ($currentName === false) {
            throw new EntityNotFoundException('role', $roleId);
        }

        // ─────────────────────────────
        // Guard: no-op rename
        // ─────────────────────────────
        if ($currentName === $newName) {
            return; // idempotent behavior
        }

        // ─────────────────────────────
        // Ensure new name is unique
        // ─────────────────────────────
        $stmtDuplicate = $this->pdo->prepare(
            'SELECT 1 FROM roles WHERE name = :name'
        );
        $stmtDuplicate->execute(['name' => $newName]);

        if ($stmtDuplicate->fetchColumn() !== false) {
            throw new EntityAlreadyExistsException('Role', 'name', $newName);
        }

        // ─────────────────────────────
        // Execute rename
        // ─────────────────────────────
        $stmt = $this->pdo->prepare(
            'UPDATE roles SET name = :name WHERE id = :id'
        );

        if ($stmt->execute([
                'id'   => $roleId,
                'name' => $newName,
            ]) === false) {
            throw new RuntimeException("Failed to rename role {$roleId}.");
        }
    }

}
