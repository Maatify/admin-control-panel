<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Ownership\SystemOwnershipRepositoryInterface;
use PDO;
use RuntimeException;

readonly class PdoSystemOwnershipRepository implements SystemOwnershipRepositoryInterface
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function exists(): bool
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM system_ownership");
        if ($stmt === false) {
            throw new RuntimeException("Failed to check system ownership.");
        }
        return (int) $stmt->fetchColumn() > 0;
    }

    public function isOwner(int $adminId): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM system_ownership WHERE admin_id = :adminId");
        $stmt->execute([':adminId' => $adminId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function assignOwner(int $adminId): void
    {
        if ($this->exists()) {
            throw new RuntimeException("System ownership already assigned.");
        }

        $stmt = $this->pdo->prepare("INSERT INTO system_ownership (admin_id) VALUES (:adminId)");
        $stmt->execute([':adminId' => $adminId]);
    }
}
