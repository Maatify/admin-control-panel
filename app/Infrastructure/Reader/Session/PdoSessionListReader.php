<?php

declare(strict_types=1);

namespace App\Infrastructure\Reader\Session;

use App\Domain\DTO\AdminConfigDTO;
use App\Domain\DTO\Common\PaginationDTO;
use App\Domain\DTO\Session\SessionListItemDTO;
use App\Domain\DTO\Session\SessionListResponseDTO;
use App\Domain\List\ListQueryDTO;
use App\Domain\Session\Reader\SessionListReaderInterface;
use App\Infrastructure\Query\ResolvedListFilters;
use PDO;
use RuntimeException;

readonly class PdoSessionListReader implements SessionListReaderInterface
{
    public function __construct(
        private PDO $pdo,
        private AdminConfigDTO $config
    ) {}

    public function getSessions(
        ListQueryDTO $query,
        ResolvedListFilters $filters,
        ?int $adminIdFilter,
        string $currentSessionHash
    ): SessionListResponseDTO
    {
        $where  = [];
        $params = [];

        // ─────────────────────────────
        // Global search (session_id OR admin_id)
        // ─────────────────────────────
        if ($filters->globalSearch !== null) {
            // Global search applies to session_id (string) AND admin_id (int)
            // We use OR logic here.
            $globalConditions = ['s.session_id LIKE :global'];
            $params['global'] = '%' . $filters->globalSearch . '%';

            // Only attempt admin_id match if input is numeric to avoid type mismatches or useless queries
            if (is_numeric($filters->globalSearch)) {
                $globalConditions[] = 's.admin_id = :global_id';
                $params['global_id'] = (int) $filters->globalSearch;
            }

            $where[] = '(' . implode(' OR ', $globalConditions) . ')';
        }

        // ─────────────────────────────
        // Column filters
        // ─────────────────────────────
        foreach ($filters->columnFilters as $column => $value) {

            if ($column === 'session_id') {
                $where[] = 's.session_id LIKE :session_id';
                $params['session_id'] = '%' . $value . '%';
            }

            if ($column === 'admin_id') {
                $where[] = 's.admin_id = :search_admin_id';
                $params['search_admin_id'] = (int) $value;
            }

            if ($column === 'status') {
                switch ($value) {
                    case 'active':
                        $where[] = 's.is_revoked = 0 AND s.expires_at > NOW()';
                        break;

                    case 'revoked':
                        $where[] = 's.is_revoked = 1';
                        break;

                    case 'expired':
                        $where[] = 's.is_revoked = 0 AND s.expires_at <= NOW()';
                        break;
                }
            }
        }

        // ─────────────────────────────
        // Date range
        // ─────────────────────────────
        if ($filters->dateFrom !== null) {
            $where[] = 's.created_at >= :date_from';
            $params['date_from'] = $filters->dateFrom->format('Y-m-d 00:00:00');
        }

        if ($filters->dateTo !== null) {
            $where[] = 's.created_at <= :date_to';
            $params['date_to'] = $filters->dateTo->format('Y-m-d 23:59:59');
        }

        // ─────────────────────────────
        // Admin scope enforcement (HARD)
        // ─────────────────────────────
        if ($adminIdFilter !== null) {
            $where[] = 's.admin_id = :admin_id';
            $params['admin_id'] = $adminIdFilter;
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // ─────────────────────────────
        // Total (no filters)
        // ─────────────────────────────
        $totalStmt = $this->pdo->query('SELECT COUNT(*) FROM admin_sessions');

        if ($totalStmt === false) {
            throw new RuntimeException('Failed to execute total count query');
        }

        $total = (int) $totalStmt->fetchColumn();

        // ─────────────────────────────
        // Filtered
        // ─────────────────────────────
        $stmtFiltered = $this->pdo->prepare(
            "SELECT COUNT(*) FROM admin_sessions s {$whereSql}"
        );
        $stmtFiltered->execute($params);
        $filtered = (int) $stmtFiltered->fetchColumn();

        // ─────────────────────────────
        // Data
        // ─────────────────────────────
        $limit  = $query->perPage;
        $offset = ($query->page - 1) * $limit;

        $sql = "
            SELECT
                s.session_id,
                s.admin_id,
                s.created_at,
                s.expires_at,
                s.is_revoked,
                CASE
                    WHEN s.is_revoked = 1 THEN 'revoked'
                    WHEN s.expires_at <= NOW() THEN 'expired'
                    ELSE 'active'
                END AS status,
                (
                    SELECT ae.email_encrypted
                    FROM admin_emails ae
                    WHERE ae.admin_id = s.admin_id
                    ORDER BY ae.id ASC
                    LIMIT 1
                ) AS email_encrypted
            FROM admin_sessions s
            {$whereSql}
            ORDER BY s.created_at DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $items = [];

        foreach ($rows ?: [] as $row) {
            $adminId    = (int) $row['admin_id'];
            $identifier = 'Admin #' . $adminId;

            if (!empty($row['email_encrypted'])) {
                $decrypted = $this->decryptEmail((string) $row['email_encrypted']);
                if ($decrypted !== null) {
                    $identifier = $decrypted;
                }
            }

            $items[] = new SessionListItemDTO(
                session_id       : (string) $row['session_id'],
                admin_id         : $adminId,
                admin_identifier : $identifier,
                created_at       : (string) $row['created_at'],
                expires_at       : (string) $row['expires_at'],
                status            : (string) $row['status'],
                is_current        : hash_equals((string) $row['session_id'], $currentSessionHash)
            );
        }

        return new SessionListResponseDTO(
            data: $items,
            pagination: new PaginationDTO(
                page     : $query->page,
                perPage  : $query->perPage,
                total    : $total,
                filtered : $filtered
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
            $ivLen  = openssl_cipher_iv_length($cipher);

            if ($ivLen === false || strlen($data) < $ivLen + 16) {
                return null;
            }

            $iv         = substr($data, 0, $ivLen);
            $tag        = substr($data, $ivLen, 16);
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
        } catch (\Throwable) {
            return null;
        }
    }
}
