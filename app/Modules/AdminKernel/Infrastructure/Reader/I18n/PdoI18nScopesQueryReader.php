<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Reader\I18n;

use Maatify\AdminKernel\Domain\DTO\Common\PaginationDTO;
use Maatify\AdminKernel\Domain\DTO\I18nScopesList\I18nScopesListItemDTO;
use Maatify\AdminKernel\Domain\DTO\I18nScopesList\I18nScopesListResponseDTO;
use Maatify\AdminKernel\Domain\I18n\Scope\Reader\I18nScopesQueryReaderInterface;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\AdminKernel\Infrastructure\Query\ResolvedListFilters;
use PDO;
use RuntimeException;

final readonly class PdoI18nScopesQueryReader implements I18nScopesQueryReaderInterface
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function queryI18nScopes(
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): I18nScopesListResponseDTO {
        $where  = [];
        $params = [];

        // ─────────────────────────────
        // Global search (free text)
        // ─────────────────────────────
        if ($filters->globalSearch !== null) {
            $g = trim($filters->globalSearch);
            if ($g !== '') {
                $where[] = '(s.code LIKE :global_text OR s.name LIKE :global_text OR s.description LIKE :global_text)';
                $params['global_text'] = '%' . $g . '%';
            }
        }

        // ─────────────────────────────
        // Column filters (explicit only)
        // ─────────────────────────────
        foreach ($filters->columnFilters as $alias => $value) {
            if ($alias === 'id') {
                $where[] = 's.id = :id';
                $params['id'] = (int)$value;
            }

            if ($alias === 'code') {
                $where[] = 's.code = :code';
                $params['code'] = trim((string)$value);
            }

            if ($alias === 'name') {
                $where[] = 's.name = :name';
                $params['name'] = trim((string)$value);
            }

            if ($alias === 'is_active') {
                $where[] = 's.is_active = :is_active';
                $params['is_active'] = (int)$value;
            }
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // ─────────────────────────────
        // Total (no filters)
        // ─────────────────────────────
        $stmtTotal = $this->pdo->query('SELECT COUNT(*) FROM i18n_scopes');
        if ($stmtTotal === false) {
            throw new RuntimeException('Failed to execute total i18n_scopes count query');
        }
        $total = (int)$stmtTotal->fetchColumn();

        // ─────────────────────────────
        // Filtered
        // ─────────────────────────────
        $stmtFiltered = $this->pdo->prepare(
            "SELECT COUNT(*) FROM i18n_scopes s {$whereSql}"
        );
        $stmtFiltered->execute($params);
        $filtered = (int)$stmtFiltered->fetchColumn();

        // ─────────────────────────────
        // Data
        // ─────────────────────────────
        $limit  = $query->perPage;
        $offset = ($query->page - 1) * $limit;

        $sql = "
            SELECT
                s.id,
                s.code,
                s.name,
                s.description,
                s.is_active,
                s.sort_order
            FROM i18n_scopes s
            {$whereSql}
            ORDER BY
                s.sort_order ASC,
                s.code ASC,
                s.id ASC
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
            $id = $row['id'] ?? null;
            $isActive = $row['is_active'] ?? null;
            $sortOrder = $row['sort_order'] ?? null;

            if (!is_int($id) && !is_string($id)) {
                continue; // corrupted row
            }

            if (!is_int($isActive) && !is_string($isActive)) {
                continue; // corrupted row
            }

            if (!is_int($sortOrder) && !is_string($sortOrder)) {
                continue; // corrupted row
            }

            $items[] = new I18nScopesListItemDTO(
                id: (int)$id,
                code: is_string($row['code'] ?? null) ? $row['code'] : '',
                name: is_string($row['name'] ?? null) ? $row['name'] : '',
                description: is_string($row['description'] ?? null) ? $row['description'] : '',
                is_active: (int)$isActive,
                sort_order: (int)$sortOrder
            );
        }

        $pagination = new PaginationDTO(
            page: $query->page,
            perPage: $query->perPage,
            total: $total,
            filtered: $filtered
        );

        return new I18nScopesListResponseDTO(
            data: $items,
            pagination: $pagination
        );
    }
}


