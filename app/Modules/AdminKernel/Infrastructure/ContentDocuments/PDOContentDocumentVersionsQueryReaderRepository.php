<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-22 00:13
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\ContentDocuments;

use Maatify\AdminKernel\Domain\ContentDocuments\ContentDocumentVersionsQueryReaderInterface;
use Maatify\AdminKernel\Domain\ContentDocuments\DTO\ContentDocumentVersionsListItemDTO;
use Maatify\AdminKernel\Domain\ContentDocuments\DTO\ContentDocumentVersionsListResponseDTO;
use Maatify\AdminKernel\Domain\DTO\Common\PaginationDTO;
use Maatify\AdminKernel\Domain\List\Filters\ResolvedListFilters;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use PDO;
use RuntimeException;

final readonly class PDOContentDocumentVersionsQueryReaderRepository implements ContentDocumentVersionsQueryReaderInterface
{
    public function __construct(
        private PDO $pdo,
    ) {
    }

    public function query(
        int $documentTypeId,
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): ContentDocumentVersionsListResponseDTO {

        if ($documentTypeId <= 0) {
            throw new RuntimeException('Invalid documentTypeId');
        }

        $where  = [];
        $params = [];

        // ─────────────────────────────
        // Enforced Scope: document_type_id (MANDATORY)
        // ─────────────────────────────
        $where[] = 'd.document_type_id = :document_type_id';
        $params['document_type_id'] = $documentTypeId;

        // ─────────────────────────────
        // Global Search
        // - numeric → document id
        // - text    → version
        // ─────────────────────────────
        if ($filters->globalSearch !== null) {
            $g = trim($filters->globalSearch);

            if ($g !== '') {
                if (is_numeric($g)) {
                    $where[] = '(d.id = :global_id)';
                    $params['global_id'] = (int) $g;
                } else {
                    $where[] = '(d.version LIKE :global_version)';
                    $params['global_version'] = '%' . $g . '%';
                }
            }
        }

        // ─────────────────────────────
        // Column Filters (explicit only)
        // ─────────────────────────────
        foreach ($filters->columnFilters as $alias => $value) {

            if ($alias === 'document_id') {
                if (is_numeric($value)) {
                    $where[] = 'd.id = :document_id';
                    $params['document_id'] = (int) $value;
                }
            }

            if ($alias === 'version') {
                $v = trim((string) $value);
                if ($v !== '') {
                    $where[] = 'd.version LIKE :version';
                    $params['version'] = '%' . $v . '%';
                }
            }

            if ($alias === 'is_active') {
                if (is_numeric($value)) {
                    $where[] = 'd.is_active = :is_active';
                    $params['is_active'] = (int) $value;
                }
            }

            if ($alias === 'requires_acceptance') {
                if (is_numeric($value)) {
                    $where[] = 'd.requires_acceptance = :requires_acceptance';
                    $params['requires_acceptance'] = (int) $value;
                }
            }

            if ($alias === 'status') {
                $status = strtolower(trim((string) $value));

                if ($status === 'draft') {
                    $where[] = '(d.archived_at IS NULL AND d.is_active = 0 AND d.published_at IS NULL)';
                }

                if ($status === 'active') {
                    $where[] = '(d.archived_at IS NULL AND d.is_active = 1)';
                }

                if ($status === 'archived') {
                    $where[] = '(d.archived_at IS NOT NULL)';
                }
            }
        }

        // ─────────────────────────────
        // Date range (standardized via ListFilterResolver)
        // ─────────────────────────────
        if ($filters->dateFrom !== null) {
            $where[] = 'd.created_at >= :date_from';
            $params['date_from'] = $filters->dateFrom->format('Y-m-d 00:00:00');
        }

        if ($filters->dateTo !== null) {
            $where[] = 'd.created_at <= :date_to';
            $params['date_to'] = $filters->dateTo->format('Y-m-d 23:59:59');
        }

        $whereSql = 'WHERE ' . implode(' AND ', $where);

        // ─────────────────────────────
        // Total (scoped, no filters)
        // ─────────────────────────────
        $stmtTotal = $this->pdo->prepare(
            'SELECT COUNT(*) FROM documents d WHERE d.document_type_id = :document_type_id'
        );

        if ($stmtTotal === false) {
            throw new RuntimeException('Failed to prepare total documents count query');
        }

        $stmtTotal->execute(['document_type_id' => $documentTypeId]);
        $total = (int) $stmtTotal->fetchColumn();

        // ─────────────────────────────
        // Filtered Count
        // ─────────────────────────────
        $stmtFiltered = $this->pdo->prepare(
            "SELECT COUNT(*) FROM documents d {$whereSql}"
        );

        if ($stmtFiltered === false) {
            throw new RuntimeException('Failed to prepare filtered documents count query');
        }

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
                d.document_type_id,
                d.type_key,
                d.version,
                d.is_active,
                d.requires_acceptance,
                d.published_at,
                d.archived_at,
                d.created_at,
                d.updated_at
            FROM documents d
            {$whereSql}
            ORDER BY d.id DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->pdo->prepare($sql);

        if ($stmt === false) {
            throw new RuntimeException('Failed to prepare documents query statement');
        }

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
            $docTypeId = $row['document_type_id'] ?? null;
            $typeKey = $row['type_key'] ?? null;
            $version = $row['version'] ?? null;
            $isActive = $row['is_active'] ?? null;
            $requiresAcceptance = $row['requires_acceptance'] ?? null;

            $publishedAt = is_string($row['published_at'] ?? null) ? (string) $row['published_at'] : null;
            $archivedAt  = is_string($row['archived_at'] ?? null) ? (string) $row['archived_at'] : null;

            $createdAt = $row['created_at'] ?? null;
            $updatedAt = is_string($row['updated_at'] ?? null) ? (string) $row['updated_at'] : null;

            if (!is_numeric($id) || !is_numeric($docTypeId)) {
                continue;
            }

            if (!is_string($typeKey) || !is_string($version)) {
                continue;
            }

            if (!is_numeric($isActive) || !is_numeric($requiresAcceptance)) {
                continue;
            }

            if (!is_string($createdAt)) {
                continue;
            }

            $items[] = new ContentDocumentVersionsListItemDTO(
                id: (int) $id,
                document_type_id: (int) $docTypeId,
                type_key: $typeKey,
                version: $version,
                is_active: (int) $isActive,
                requires_acceptance: (int) $requiresAcceptance,
                published_at: $publishedAt,
                archived_at: $archivedAt,
                created_at: $createdAt,
                updated_at: $updatedAt,
            );
        }

        $pagination = new PaginationDTO(
            page: $query->page,
            perPage: $query->perPage,
            total: $total,
            filtered: $filtered
        );

        return new ContentDocumentVersionsListResponseDTO(
            data: $items,
            pagination: $pagination
        );
    }
}