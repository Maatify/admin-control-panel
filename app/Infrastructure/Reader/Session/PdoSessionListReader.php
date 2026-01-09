<?php

declare(strict_types=1);

namespace App\Infrastructure\Reader\Session;

use App\Domain\DTO\AdminConfigDTO;
use App\Domain\DTO\Session\SessionListItemDTO;
use App\Domain\DTO\Session\SessionListQueryDTO;
use App\Domain\DTO\Session\SessionListResponseDTO;
use App\Domain\Session\Reader\SessionListReaderInterface;
use App\Domain\DTO\Common\PaginationDTO;
use PDO;

class PdoSessionListReader implements SessionListReaderInterface
{
    public function __construct(
        private PDO $pdo,
        private AdminConfigDTO $config
    ) {
    }

    public function getSessions(SessionListQueryDTO $query): SessionListResponseDTO
    {
        // 1. Build Query Conditions
        $conditions = [];
        $params = [];

        // Apply Filters
        if (!empty($query->filters['session_id'])) {
            $conditions[] = "s.session_id LIKE :session_id";
            $params[':session_id'] = '%' . $query->filters['session_id'] . '%';
        }

        if (!empty($query->filters['status'])) {
            $status = $query->filters['status'];
            if ($status === 'active') {
                $conditions[] = "s.is_revoked = 0 AND s.expires_at > NOW()";
            } elseif ($status === 'revoked') {
                $conditions[] = "s.is_revoked = 1";
            } elseif ($status === 'expired') {
                $conditions[] = "s.is_revoked = 0 AND s.expires_at <= NOW()";
            }
        }

        if ($query->admin_id !== null) {
            $conditions[] = "s.admin_id = :admin_id";
            $params[':admin_id'] = $query->admin_id;
        }

        $whereClause = !empty($conditions) ? " WHERE " . implode(' AND ', $conditions) : "";

        // 2. Count Total
        $countSql = "SELECT COUNT(*) FROM admin_sessions s" . $whereClause;
        $countStmt = $this->pdo->prepare($countSql);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $total = (int)$countStmt->fetchColumn();

        // 3. Fetch Data
        // Use subquery to get ONE email per admin to avoid row multiplication
        $sql = "SELECT
                    s.session_id,
                    s.admin_id,
                    s.created_at,
                    s.expires_at,
                    s.is_revoked,
                    CASE
                        WHEN s.is_revoked = 1 THEN 'revoked'
                        WHEN s.expires_at <= NOW() THEN 'expired'
                        ELSE 'active'
                    END as status,
                    (
                        SELECT ae.email_encrypted
                        FROM admin_emails ae
                        WHERE ae.admin_id = s.admin_id
                        ORDER BY ae.id ASC
                        LIMIT 1
                    ) as email_encrypted
                FROM admin_sessions s" . $whereClause;

        $sql .= " ORDER BY s.created_at DESC LIMIT :limit OFFSET :offset";

        $limit = $query->per_page;
        $offset = ($query->page - 1) * $limit;

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $items = [];
        if ($results !== false) {
             foreach ($results as $row) {
                /** @var array{session_id: string, admin_id: int|string, created_at: string, expires_at: string, is_revoked: int, status: string, email_encrypted: string|null} $row */

                $adminId = (int)$row['admin_id'];

                // Decrypt Email
                $identifier = "Admin #$adminId";
                $encryptedEmail = $row['email_encrypted'] ?? null;

                if ($encryptedEmail !== null && $encryptedEmail !== '') {
                    $decrypted = $this->decryptEmail($encryptedEmail);
                    if ($decrypted !== null) {
                        $identifier = $decrypted;
                    }
                }

                $isCurrent = hash_equals($row['session_id'], $query->current_session_id);

                $items[] = new SessionListItemDTO(
                    session_id: (string)$row['session_id'],
                    admin_id: $adminId,
                    admin_identifier: $identifier,
                    created_at: (string)$row['created_at'],
                    expires_at: (string)$row['expires_at'],
                    status: (string)$row['status'],
                    is_current: $isCurrent
                );
            }
        }

        return new SessionListResponseDTO(
            data: $items,
            pagination: new PaginationDTO(
                page: $query->page,
                perPage: $query->per_page,
                total: $total
            )
        );
    }

    private function decryptEmail(string $encryptedEmail): ?string
    {
        try {
            $data = base64_decode($encryptedEmail, true);
            if ($data === false) {
                return null;
            }

            $cipher = 'aes-256-gcm';
            $ivLen = openssl_cipher_iv_length($cipher);
            if ($ivLen === false) {
                return null;
            }

            if (strlen($data) < $ivLen + 16) {
                return null;
            }

            $iv = substr($data, 0, $ivLen);
            $tag = substr($data, $ivLen, 16);
            $ciphertext = substr($data, $ivLen + 16);

            $decrypted = openssl_decrypt(
                $ciphertext,
                $cipher,
                $this->config->emailEncryptionKey,
                OPENSSL_RAW_DATA,
                $iv,
                $tag
            );

            return $decrypted !== false ? $decrypted : null;
        } catch (\Throwable $e) {
            // Silently fail on decryption errors to allow list rendering
            return null;
        }
    }
}
