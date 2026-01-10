<?php

declare(strict_types=1);

namespace App\Infrastructure\Reader\Admin;

use App\Domain\Admin\Reader\AdminQueryReaderInterface;
use App\Domain\DTO\AdminConfigDTO;
use App\Domain\DTO\AdminList\AdminListItemDTO;
use App\Domain\DTO\AdminList\AdminListResponseDTO;
use App\Domain\DTO\Common\PaginationDTO;
use App\Domain\List\ListQueryDTO;
use App\Infrastructure\Query\ResolvedListFilters;
use PDO;

final readonly class PdoAdminQueryReader implements AdminQueryReaderInterface
{
    public function __construct(
        private PDO $pdo,
        private AdminConfigDTO $config
    ) {}

    public function queryAdmins(
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): AdminListResponseDTO
    {
        $where  = [];
        $params = [];

        // ─────────────────────────────
        // Global search (Admins: ID OR Email)
        // ─────────────────────────────
        if ($filters->globalSearch !== null) {
            $g = trim($filters->globalSearch);

            // ID search
            if ($g !== '' && ctype_digit($g)) {
                $where[] = 'a.id = :global_id';
                $params['global_id'] = (int) $g;
            }
            // Email search
            elseif ($g !== '' && filter_var($g, FILTER_VALIDATE_EMAIL)) {
                $blind = hash_hmac(
                    'sha256',
                    strtolower($g),
                    $this->config->emailBlindIndexKey
                );

                $where[] = 'ae.email_blind_index = :global_email';
                $params['global_email'] = $blind;
            }
            // else: ignore invalid global search
        }

        // ─────────────────────────────
        // Column filters (explicit only)
        // ─────────────────────────────
        foreach ($filters->columnFilters as $alias => $value) {
            if ($alias === 'id') {
                $where[] = 'a.id = :admin_id';
                $params['admin_id'] = (int) $value;
            }

            if ($alias === 'email' && filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $blind = hash_hmac(
                    'sha256',
                    strtolower((string) $value),
                    $this->config->emailBlindIndexKey
                );

                $where[] = 'ae.email_blind_index = :email';
                $params['email'] = $blind;
            }
        }

        // ─────────────────────────────
        // Date range
        // ─────────────────────────────
        if ($filters->dateFrom !== null) {
            $where[] = 'a.created_at >= :date_from';
            $params['date_from'] = $filters->dateFrom->format('Y-m-d 00:00:00');
        }

        if ($filters->dateTo !== null) {
            $where[] = 'a.created_at <= :date_to';
            $params['date_to'] = $filters->dateTo->format('Y-m-d 23:59:59');
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // ─────────────────────────────
        // Total (no filters)
        // ─────────────────────────────
        $stmtTotal = $this->pdo->query('SELECT COUNT(*) FROM admins');

        if ($stmtTotal === false) {
            throw new \RuntimeException('Failed to execute total admins count query');
        }

        $total = (int) $stmtTotal->fetchColumn();

        // ─────────────────────────────
        // Filtered
        // ─────────────────────────────
        $stmtFiltered = $this->pdo->prepare(
            "SELECT COUNT(DISTINCT a.id)
             FROM admins a
             LEFT JOIN admin_emails ae ON ae.admin_id = a.id
             {$whereSql}"
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
                a.id,
                a.created_at,
                (
                    SELECT ae2.email_encrypted
                    FROM admin_emails ae2
                    WHERE ae2.admin_id = a.id
                    ORDER BY ae2.id ASC
                    LIMIT 1
                ) AS email_encrypted
            FROM admins a
            LEFT JOIN admin_emails ae ON ae.admin_id = a.id
            {$whereSql}
            GROUP BY a.id
            ORDER BY a.created_at DESC 
            `LIMIT` :limit OFFSET :offset
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
            $email = 'N/A';

            if (!empty($row['email_encrypted'])) {
                $decrypted = $this->decryptEmail((string) $row['email_encrypted']);
                if ($decrypted !== null) {
                    $email = $decrypted;
                }
            }

            $items[] = new AdminListItemDTO(
                id: (int) $row['id'],
                email: $email,
                createdAt: (string) $row['created_at']
            );
        }

        return new AdminListResponseDTO(
            data: $items,
            pagination: new PaginationDTO(
                page: $query->page,
                perPage: $query->perPage,
                total: $total,
                filtered: $filtered
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
