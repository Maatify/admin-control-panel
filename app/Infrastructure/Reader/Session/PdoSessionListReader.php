<?php

declare(strict_types=1);

namespace App\Infrastructure\Reader\Session;

use App\Domain\DTO\Session\SessionListItemDTO;
use App\Domain\DTO\Session\SessionListQueryDTO;
use App\Domain\DTO\Session\SessionListResponseDTO;
use App\Domain\Session\Reader\SessionListReaderInterface;
use PDO;

class PdoSessionListReader implements SessionListReaderInterface
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function getSessions(SessionListQueryDTO $query): SessionListResponseDTO
    {
        $sql = "SELECT SQL_CALC_FOUND_ROWS
                    session_id,
                    created_at,
                    expires_at,
                    is_revoked,
                    CASE
                        WHEN is_revoked = 1 THEN 'revoked'
                        WHEN expires_at <= NOW() THEN 'expired'
                        ELSE 'active'
                    END as status
                FROM admin_sessions";

        $conditions = [];
        $params = [];

        // Apply Filters
        if (!empty($query->filters['session_id'])) {
            $conditions[] = "session_id LIKE :session_id";
            $params[':session_id'] = '%' . $query->filters['session_id'] . '%';
        }

        if (!empty($query->filters['status'])) {
            $status = $query->filters['status'];
            if ($status === 'active') {
                $conditions[] = "is_revoked = 0 AND expires_at > NOW()";
            } elseif ($status === 'revoked') {
                $conditions[] = "is_revoked = 1";
            } elseif ($status === 'expired') {
                $conditions[] = "is_revoked = 0 AND expires_at <= NOW()";
            }
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        // Pagination
        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

        $limit = $query->per_page;
        $offset = ($query->page - 1) * $limit;

        $stmt = $this->pdo->prepare($sql);

        // Bind params manually to ensure correct types for limit/offset
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get total count
        $countStmt = $this->pdo->query("SELECT FOUND_ROWS()");
        $total = $countStmt ? (int)$countStmt->fetchColumn() : 0;

        $items = [];
        foreach ($results as $row) {
            $items[] = new SessionListItemDTO(
                session_id: (string)$row['session_id'],
                created_at: (string)$row['created_at'],
                expires_at: (string)$row['expires_at'],
                status: (string)$row['status']
            );
        }

        return new SessionListResponseDTO(
            data: $items,
            pagination: [
                'page' => $query->page,
                'per_page' => $query->per_page,
                'total' => $total,
            ]
        );
    }
}
