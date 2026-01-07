<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Contracts\AdminQueryReaderInterface;
use App\Domain\DTO\Admin\AdminListItemDTO;
use App\Domain\DTO\Admin\AdminListQueryDTO;
use App\Domain\DTO\AdminConfigDTO;
use PDO;

class PdoAdminListReader implements AdminQueryReaderInterface
{
    public function __construct(
        private PDO $pdo,
        private AdminConfigDTO $config
    ) {}

    public function getAdmins(AdminListQueryDTO $query): array
    {
        $page = max(1, $query->page);
        $perPage = max(1, $query->perPage);
        $offset = ($page - 1) * $perPage;

        // Build Query
        $sql = "
            SELECT
                a.id,
                a.created_at,
                ae.email_encrypted,
                ae.verification_status,
                so.id as system_ownership_id,
                GROUP_CONCAT(r.name) as role_names
            FROM admins a
            JOIN admin_emails ae ON a.id = ae.admin_id
            LEFT JOIN system_ownership so ON a.id = so.admin_id
            LEFT JOIN admin_roles ar ON a.id = ar.admin_id
            LEFT JOIN roles r ON ar.role_id = r.id
        ";

        // Filtering
        $where = [];
        $params = [];

        if (isset($query->filters['id']) && is_numeric($query->filters['id'])) {
            $where[] = "a.id = ?";
            $params[] = (int)$query->filters['id'];
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $sql .= " GROUP BY a.id, a.created_at, ae.email_encrypted, ae.verification_status, so.id";
        $sql .= " ORDER BY a.id DESC";
        $sql .= " LIMIT ? OFFSET ?";

        // Count Query
        $countSql = "SELECT COUNT(DISTINCT a.id) FROM admins a JOIN admin_emails ae ON a.id = ae.admin_id";
        if (!empty($where)) {
            $countSql .= " WHERE " . implode(' AND ', $where);
        }

        // Execute Count
        $stmtCount = $this->pdo->prepare($countSql);
        $stmtCount->execute(array_slice($params, 0, count($where) > 0 ? count($params) : 0)); // Only where params
        $total = (int)$stmtCount->fetchColumn();

        // Execute Data
        $stmt = $this->pdo->prepare($sql);
        // Add limit/offset to params
        $params[] = $perPage;
        $params[] = $offset;
        $stmt->execute($params);

        $items = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            /** @var array{id: int|string, created_at: string, email_encrypted: string, verification_status: string, system_ownership_id: int|null, role_names: string|null} $row */

            // Decrypt Email
            $email = $this->decryptEmail($row['email_encrypted']);

            $roles = $row['role_names'] ? explode(',', $row['role_names']) : [];

            $items[] = new AdminListItemDTO(
                (int)$row['id'],
                $email,
                $row['verification_status'],
                new \DateTimeImmutable($row['created_at']),
                $roles,
                !is_null($row['system_ownership_id'])
            );
        }

        return [
            'data' => $items,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total
            ]
        ];
    }

    private function decryptEmail(string $encryptedPayload): string
    {
        $key = $this->config->emailEncryptionKey;
        $data = base64_decode($encryptedPayload, true);
        if ($data === false) {
            return 'INVALID_BASE64';
        }

        // IV (12) + Tag (16) + Ciphertext
        if (strlen($data) < 28) {
             return 'INVALID_PAYLOAD_LENGTH';
        }

        $iv = substr($data, 0, 12);
        $tag = substr($data, 12, 16);
        $ciphertext = substr($data, 28);

        $result = openssl_decrypt($ciphertext, 'aes-256-gcm', $key, 0, $iv, $tag);

        return $result !== false ? $result : 'DECRYPTION_FAILED';
    }
}
