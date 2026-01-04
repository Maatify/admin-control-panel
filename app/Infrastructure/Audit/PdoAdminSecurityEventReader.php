<?php

declare(strict_types=1);

namespace App\Infrastructure\Audit;

use App\Domain\Contracts\AdminSecurityEventReaderInterface;
use App\Domain\DTO\Audit\GetMySecurityEventsQueryDTO;
use App\Domain\DTO\Audit\SecurityEventViewDTO;
use PDO;

class PdoAdminSecurityEventReader implements AdminSecurityEventReaderInterface
{
    public function __construct(
        private PDO $pdo
    ) {
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
