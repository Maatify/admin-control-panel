<?php

declare(strict_types=1);

namespace App\Infrastructure\Audit;

use App\Domain\Contracts\AdminTargetedAuditReaderInterface;
use App\Domain\DTO\Audit\AuditLogViewDTO;
use App\Domain\DTO\Audit\GetActionsTargetingMeQueryDTO;
use PDO;

class PdoAdminTargetedAuditReader implements AdminTargetedAuditReaderInterface
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    /**
     * @return array<AuditLogViewDTO>
     */
    public function getActionsTargetingMe(GetActionsTargetingMeQueryDTO $query): array
    {
        // Target type must be 'admin' and target_id match the admin ID
        $sql = "SELECT id, actor_admin_id, target_type, target_id, action, changes, created_at
                FROM audit_logs
                WHERE target_type = 'admin' AND target_id = :target_admin_id";

        $params = [':target_admin_id' => (string)$query->targetAdminId];

        if ($query->actorAdminId !== null) {
            $sql .= ' AND actor_admin_id = :actor_admin_id';
            $params[':actor_admin_id'] = $query->actorAdminId;
        }

        if ($query->action !== null) {
            $sql .= ' AND action = :action';
            $params[':action'] = $query->action;
        }

        if ($query->startDate !== null) {
            $sql .= ' AND created_at >= :start_date';
            $params[':start_date'] = $query->startDate->format('Y-m-d H:i:s');
        }

        if ($query->endDate !== null) {
            $sql .= ' AND created_at <= :end_date';
            $params[':end_date'] = $query->endDate->format('Y-m-d H:i:s');
        }

        $sql .= ' ORDER BY created_at DESC LIMIT :limit OFFSET :offset';

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $query->limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', ($query->page - 1) * $query->limit, PDO::PARAM_INT);

        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!is_array($results)) {
            return [];
        }

        $dtos = [];
        foreach ($results as $row) {
            /** @var array{id: int, actor_admin_id: int|null, target_type: string, target_id: string|int, action: string, changes: string, created_at: string} $row */

            $changes = json_decode($row['changes'], true);
            if (!is_array($changes)) {
                $changes = [];
            }

            $dtos[] = new AuditLogViewDTO(
                (int)$row['id'],
                $row['actor_admin_id'] !== null ? (int)$row['actor_admin_id'] : null,
                $row['target_type'],
                (string)$row['target_id'],
                $row['action'],
                $changes,
                $row['created_at']
            );
        }

        return $dtos;
    }
}
