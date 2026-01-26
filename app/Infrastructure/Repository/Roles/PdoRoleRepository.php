<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Roles;

use App\Domain\Contracts\Roles\RoleRepositoryInterface;
use App\Domain\Contracts\Roles\RolesMetadataRepositoryInterface;
use PDO;
use RuntimeException;

class PdoRoleRepository implements RoleRepositoryInterface, RolesMetadataRepositoryInterface
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
