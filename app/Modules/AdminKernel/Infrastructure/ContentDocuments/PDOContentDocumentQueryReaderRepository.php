<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm)
 * @since       2026-02-19
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\ContentDocuments;

use Maatify\AdminKernel\Domain\ContentDocuments\ContentDocumentQueryReaderInterface;
use Maatify\AdminKernel\Domain\ContentDocuments\DTO\ContentDocumentListItemDTO;
use Maatify\AdminKernel\Domain\ContentDocuments\DTO\ContentDocumentListResponseDTO;
use Maatify\AdminKernel\Domain\DTO\Common\PaginationDTO;
use Maatify\AdminKernel\Domain\List\Filters\ResolvedListFilters;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use PDO;
use RuntimeException;

final readonly class PDOContentDocumentQueryReaderRepository implements ContentDocumentQueryReaderInterface
{
    public function __construct(
        private PDO $pdo,
    ) {
    }

    public function query(
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): ContentDocumentListResponseDTO {

        $where  = [];
        $params = [];

        // ─────────────────────────────
        // Global Search
        // ─────────────────────────────
        if ($filters->globalSearch !== null) {
            $g = trim($filters->globalSearch);

            if ($g !== '') {
                $where[] = '(d.key LIKE :global_text)';
                $params['global_text'] = '%' . $g . '%';
            }
        }

        // ─────────────────────────────
        // Column Filters
        // ─────────────────────────────
        foreach ($filters->columnFilters as $alias => $value) {

            if ($alias === 'id') {
                $where[] = 'd.id = :id';
                $params['id'] = (int) $value;
            }

            if ($alias === 'key') {
                $where[] = 'd.key LIKE :key';
                $params['key'] = '%' . trim((string) $value) . '%';
            }

            if ($alias === 'requires_acceptance_default') {
                $where[] = 'd.requires_acceptance_default = :requires_acceptance_default';
                $params['requires_acceptance_default'] = (int) $value;
            }

            if ($alias === 'is_system') {
                $where[] = 'd.is_system = :is_system';
                $params['is_system'] = (int) $value;
            }
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // ─────────────────────────────
        // Total (no filters)
        // ─────────────────────────────
        $stmtTotal = $this->pdo->query(
            'SELECT COUNT(*) FROM document_types d'
        );

        if ($stmtTotal === false) {
            throw new RuntimeException('Failed to execute total document_types count query');
        }

        $total = (int) $stmtTotal->fetchColumn();

        // ─────────────────────────────
        // Filtered Count
        // ─────────────────────────────
        $stmtFiltered = $this->pdo->prepare(
            "SELECT COUNT(*) FROM document_types d {$whereSql}"
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
                d.id,
                d.key,
                d.requires_acceptance_default,
                d.is_system,
                d.created_at,
                d.updated_at
            FROM document_types d
            {$whereSql}
            ORDER BY
                d.id DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $items = [];

        foreach ($rows as $row) {

            $id   = $row['id'] ?? null;
            $key  = $row['key'] ?? null;
            $req  = $row['requires_acceptance_default'] ?? null;
            $sys  = $row['is_system'] ?? null;
            $cAt  = $row['created_at'] ?? null;
            $uAt = is_string($row['updated_at']) ? (string)$row['updated_at'] : null;

            if (!is_numeric($id)) {
                continue;
            }

            if (!is_string($key)) {
                continue;
            }

            if (!is_numeric($req) || !is_numeric($sys)) {
                continue;
            }

            if (!is_string($cAt)) {
                continue;
            }

            $items[] = new ContentDocumentListItemDTO(
                id: (int) $id,
                key: $key,
                requires_acceptance_default: (int) $req,
                is_system: (int) $sys,
                created_at: $cAt,
                updated_at: $uAt,
            );
        }

        $pagination = new PaginationDTO(
            page: $query->page,
            perPage: $query->perPage,
            total: $total,
            filtered: $filtered
        );

        return new ContentDocumentListResponseDTO(
            data: $items,
            pagination: $pagination
        );
    }
}
