<?php

declare(strict_types=1);

namespace App\Infrastructure\Audit;

use App\Domain\Contracts\AdminSelfAuditReaderInterface;
use App\Domain\DTO\Audit\AuditLogViewDTO;
use App\Domain\DTO\Audit\GetMyActionsQueryDTO;
use App\Domain\DTO\Audit\GetMySecurityEventsQueryDTO;
use App\Domain\DTO\Audit\SecurityEventViewDTO;
use PDO;

class PdoAdminSelfAuditReader implements AdminSelfAuditReaderInterface
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    /**
     * @return array<AuditLogViewDTO>
     */
    public function getMyActions(GetMyActionsQueryDTO $query): array
    {
        $sql = 'SELECT id, actor_admin_id, target_type, target_id, action, changes, created_at
                FROM audit_logs
                WHERE actor_admin_id = :actor_admin_id';

        $params = [':actor_admin_id' => $query->actorAdminId];

        if ($query->action !== null) {
            $sql .= ' AND action = :action';
            $params[':action'] = $query->action;
        }

        if ($query->targetType !== null) {
            $sql .= ' AND target_type = :target_type';
            $params[':target_type'] = $query->targetType;
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

        // PDO::fetchAll might return false on error, but typically returns array.
        // PHPStan strict check complains if we check for false when it thinks it's array.
        // However, standard PDO documentation says it returns array|false.
        // If PHPStan thinks it's array, we can skip the check or rely on type casting.
        // To be safe and satisfy PHPStan "always false" error, we remove the check if we are sure,
        // or suppress it. But here we can just cast to array if we want, or check using `is_array`.
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

    /**
     * @return array<SecurityEventViewDTO>
     */
    public function getMySecurityEvents(GetMySecurityEventsQueryDTO $query): array
    {
        $sql = 'SELECT id, admin_id, event_type, context, created_at
                FROM security_events
                WHERE admin_id = :admin_id';

        $params = [':admin_id' => $query->adminId];

        if ($query->eventType !== null) {
            $sql .= ' AND event_type = :event_type';
            $params[':event_type'] = $query->eventType;
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
            /** @var array{id: int, admin_id: int, event_type: string, context: string, created_at: string} $row */

            $context = json_decode($row['context'], true);
            if (!is_array($context)) {
                $context = [];
            }

            $dtos[] = new SecurityEventViewDTO(
                (int)$row['id'],
                (int)$row['admin_id'],
                $row['event_type'],
                $context,
                $row['created_at']
            );
        }

        return $dtos;
    }
}
