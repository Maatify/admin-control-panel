<?php

declare(strict_types=1);

namespace App\Infrastructure\Reader\Admin;

use App\Application\Crypto\AdminIdentifierCryptoServiceInterface;
use App\Domain\Admin\Reader\AdminQueryReaderInterface;
use App\Domain\DTO\AdminList\AdminListItemDTO;
use App\Domain\DTO\AdminList\AdminListResponseDTO;
use App\Domain\DTO\Common\PaginationDTO;
use App\Domain\DTO\Crypto\EncryptedPayloadDTO;
use App\Domain\List\ListQueryDTO;
use App\Infrastructure\Query\ResolvedListFilters;
use PDO;

final readonly class PdoAdminQueryReader implements AdminQueryReaderInterface
{
    public function __construct(
        private PDO $pdo,
        private AdminIdentifierCryptoServiceInterface $cryptoService
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
                $blind = $this->cryptoService->deriveEmailBlindIndex(strtolower($g));

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
                $blind = $this->cryptoService->deriveEmailBlindIndex(strtolower((string) $value));

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
                (SELECT email_ciphertext FROM admin_emails ae WHERE ae.admin_id = a.id ORDER BY id ASC LIMIT 1) as email_ciphertext,
                (SELECT email_iv FROM admin_emails ae WHERE ae.admin_id = a.id ORDER BY id ASC LIMIT 1) as email_iv,
                (SELECT email_tag FROM admin_emails ae WHERE ae.admin_id = a.id ORDER BY id ASC LIMIT 1) as email_tag,
                (SELECT email_key_id FROM admin_emails ae WHERE ae.admin_id = a.id ORDER BY id ASC LIMIT 1) as email_key_id
            FROM admins a
            LEFT JOIN admin_emails ae ON ae.admin_id = a.id
            {$whereSql}
            GROUP BY a.id
            ORDER BY a.created_at DESC 
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
            $email = 'N/A';

            if (!empty($row['email_ciphertext'])) {
                $ciphertext = $row['email_ciphertext'];
                $iv = $row['email_iv'];
                $tag = $row['email_tag'];

                if (is_resource($ciphertext)) {
                    $ciphertext = stream_get_contents($ciphertext);
                }
                if (is_resource($iv)) {
                    $iv = stream_get_contents($iv);
                }
                if (is_resource($tag)) {
                    $tag = stream_get_contents($tag);
                }

                $dto = new EncryptedPayloadDTO(
                    ciphertext: (string)$ciphertext,
                    iv: (string)$iv,
                    tag: (string)$tag,
                    keyId: (string)$row['email_key_id']
                );
                $decrypted = $this->cryptoService->decryptEmail($dto);
                if ($decrypted !== '') {
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

}
