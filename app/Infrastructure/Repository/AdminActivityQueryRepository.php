<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Contracts\AdminActivityQueryInterface;
use App\Domain\DTO\AdminActivityDTO;
use App\Infrastructure\UX\AdminActivityMapper;
use PDO;

class AdminActivityQueryRepository implements AdminActivityQueryInterface
{
    private PDO $pdo;
    private AdminActivityMapper $mapper;

    public function __construct(PDO $pdo, AdminActivityMapper $mapper)
    {
        $this->pdo = $pdo;
        $this->mapper = $mapper;
    }

    /**
     * @param int $adminId
     * @param int $limit
     * @return AdminActivityDTO[]
     */
    public function findByActor(int $adminId, int $limit = 20): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT actor_admin_id, target_type, target_id, action, changes, occurred_at
             FROM audit_logs
             WHERE actor_admin_id = :admin_id
             ORDER BY occurred_at DESC
             LIMIT :limit'
        );

        $stmt->bindValue(':admin_id', $adminId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            /** @var array<string, mixed> $row */
            $results[] = $this->mapper->map($row);
        }

        return $results;
    }

    /**
     * @param string $targetType
     * @param int $targetId
     * @param int $limit
     * @return AdminActivityDTO[]
     */
    public function findByTarget(string $targetType, int $targetId, int $limit = 20): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT actor_admin_id, target_type, target_id, action, changes, occurred_at
             FROM audit_logs
             WHERE target_type = :target_type AND target_id = :target_id
             ORDER BY occurred_at DESC
             LIMIT :limit'
        );

        $stmt->bindValue(':target_type', $targetType, PDO::PARAM_STR);
        $stmt->bindValue(':target_id', $targetId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            /** @var array<string, mixed> $row */
            $results[] = $this->mapper->map($row);
        }

        return $results;
    }
}
