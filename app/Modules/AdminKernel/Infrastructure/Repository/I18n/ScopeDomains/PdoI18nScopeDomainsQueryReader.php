<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-08 17:42
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Repository\I18n\ScopeDomains;

use Maatify\AdminKernel\Domain\DTO\Common\PaginationDTO;
use Maatify\AdminKernel\Domain\DTO\I18n\ScopeDomains\I18nScopeDomainsListItemDTO;
use Maatify\AdminKernel\Domain\DTO\I18n\ScopeDomains\I18nScopeDomainsListResponseDTO;
use Maatify\AdminKernel\Domain\I18n\ScopeDomains\I18nScopeDomainsQueryReaderInterface;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\AdminKernel\Infrastructure\Query\ResolvedListFilters;
use PDO;
use RuntimeException;

final readonly class PdoI18nScopeDomainsQueryReader implements I18nScopeDomainsQueryReaderInterface
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function queryScopeDomains(
        string $scopeCode,
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): I18nScopeDomainsListResponseDTO {
        // ─────────────────────────────
        // Filters
        // ─────────────────────────────
        $where  = [];
        $params = [];

        if ($filters->globalSearch !== null) {
            $g = trim($filters->globalSearch);
            if ($g !== '') {
                $where[] = '(d.code LIKE :global_text OR d.name LIKE :global_text OR d.description LIKE :global_text)';
                $params['global_text'] = '%' . $g . '%';
            }
        }

        foreach ($filters->columnFilters as $alias => $value) {
            if ($alias === 'id') {
                $where[] = 'd.id = :id';
                $params['id'] = (int) $value;
            }

            if ($alias === 'code') {
                $where[] = 'd.code = :code';
                $params['code'] = trim((string) $value);
            }

            if ($alias === 'name') {
                $where[] = 'd.name = :name';
                $params['name'] = trim((string) $value);
            }

            if ($alias === 'is_active') {
                $where[] = 'd.is_active = :is_active';
                $params['is_active'] = (int) $value;
            }
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // ─────────────────────────────
        // Total (no filters)
        // ─────────────────────────────
        $stmtTotal = $this->pdo->query('SELECT COUNT(*) FROM i18n_domains');
        if ($stmtTotal === false) {
            throw new RuntimeException('Failed to execute total i18n_domains count query');
        }
        $total = (int) $stmtTotal->fetchColumn();

        // ─────────────────────────────
        // Filtered
        // ─────────────────────────────
        $stmtFiltered = $this->pdo->prepare(
            "SELECT COUNT(*) FROM i18n_domains d {$whereSql}"
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
                d.code,
                d.name,
                d.description,
                d.is_active,
                d.sort_order,
                CASE
                    WHEN ds.domain_code IS NULL THEN 0
                    ELSE 1
                END AS assigned
            FROM i18n_domains d
            LEFT JOIN i18n_domain_scopes ds
                ON ds.domain_code = d.code
               AND ds.scope_code  = :scope_code
            {$whereSql}
            ORDER BY
                d.sort_order ASC,
                d.code ASC,
                d.id ASC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->pdo->prepare($sql);

        // Join param
        $stmt->bindValue(':scope_code', $scopeCode);

        // Where params
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
            $id        = $row['id'] ?? null;
            $isActive  = $row['is_active'] ?? null;
            $sortOrder = $row['sort_order'] ?? null;
            $assigned  = $row['assigned'] ?? null;

            if (!is_int($id) && !is_string($id)) {
                continue;
            }

            if (!is_int($isActive) && !is_string($isActive)) {
                continue;
            }

            if (!is_int($sortOrder) && !is_string($sortOrder)) {
                continue;
            }

            if (!is_int($assigned) && !is_string($assigned)) {
                continue;
            }

            $items[] = new I18nScopeDomainsListItemDTO(
                id: (int) $id,
                code: is_string($row['code'] ?? null) ? $row['code'] : '',
                name: is_string($row['name'] ?? null) ? $row['name'] : '',
                description: is_string($row['description'] ?? null) ? $row['description'] : '',
                is_active: (int) $isActive,
                sort_order: (int) $sortOrder,
                assigned: (int) $assigned
            );
        }

        return new I18nScopeDomainsListResponseDTO(
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
