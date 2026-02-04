<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Reader\I18n;

use Maatify\AdminKernel\Domain\DTO\Common\PaginationDTO;
use Maatify\AdminKernel\Domain\DTO\LanguageList\LanguageListItemDTO;
use Maatify\AdminKernel\Domain\DTO\LanguageList\LanguageListResponseDTO;
use Maatify\AdminKernel\Domain\I18n\Reader\LanguageQueryReaderInterface;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\AdminKernel\Infrastructure\Query\ResolvedListFilters;
use Maatify\I18n\Enum\TextDirectionEnum;
use PDO;
use RuntimeException;

final readonly class PdoLanguageQueryReader implements LanguageQueryReaderInterface
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function queryLanguages(
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): LanguageListResponseDTO
    {
        $where  = [];
        $params = [];

        // ─────────────────────────────
        // Global search (free text: name OR code)
        // ─────────────────────────────
        if ($filters->globalSearch !== null) {
            $g = trim($filters->globalSearch);

            if ($g !== '') {
                $where[] = '(l.name LIKE :global_text OR l.code LIKE :global_text)';
                $params['global_text'] = '%' . $g . '%';
            }
        }

        // ─────────────────────────────
        // Column filters (explicit only)
        // ─────────────────────────────
        foreach ($filters->columnFilters as $alias => $value) {

            if ($alias === 'id') {
                $where[] = 'l.id = :id';
                $params['id'] = (int)$value;
            }

            if ($alias === 'name') {
                $where[] = 'l.name LIKE :name';
                $params['name'] = '%' . trim((string)$value) . '%';
            }

            if ($alias === 'code') {
                $where[] = 'l.code = :code';
                $params['code'] = trim((string)$value);
            }

            if ($alias === 'is_active') {
                $where[] = 'l.is_active = :is_active';
                $params['is_active'] = (int)$value;
            }

            if ($alias === 'direction') {
                $where[] = 'ls.direction = :direction';
                $params['direction'] = trim((string)$value);
            }
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // ─────────────────────────────
        // Total (no filters)
        // ─────────────────────────────
        $stmtTotal = $this->pdo->query('SELECT COUNT(*) FROM languages');

        if ($stmtTotal === false) {
            throw new RuntimeException('Failed to execute total languages count query');
        }

        $total = (int)$stmtTotal->fetchColumn();

        $fromSql = '
    FROM languages l
    LEFT JOIN language_settings ls ON ls.language_id = l.id
';
        // ─────────────────────────────
        // Filtered
        // ─────────────────────────────
        $stmtFiltered = $this->pdo->prepare(
            "SELECT COUNT(*) {$fromSql} {$whereSql}"
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
                l.id,
                l.name,
                l.code,
                l.is_active,
                l.fallback_language_id,
                l.created_at,
                l.updated_at,
                ls.direction,
                ls.icon,
                ls.sort_order
            {$fromSql}
            {$whereSql}
            ORDER BY
                ls.sort_order ASC,
                l.id ASC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $items = [];

        foreach ($rows as $row) {
            $items[] = new LanguageListItemDTO(
                id: (int)$row['id'],
                name: (string)$row['name'],
                code: (string)$row['code'],
                isActive: ((int)$row['is_active']) === 1,
                fallbackLanguageId: $row['fallback_language_id'] !== null
                    ? (int)$row['fallback_language_id']
                    : null,
                direction: isset($row['direction'])
                    ? TextDirectionEnum::from((string)$row['direction'])
                    : TextDirectionEnum::LTR,
                icon: is_string($row['icon'] ?? null) ? $row['icon'] : null,
                sortOrder: isset($row['sort_order']) ? (int)$row['sort_order'] : 0,
                createdAt: (string)$row['created_at'],
                updatedAt: is_string($row['updated_at'] ?? null) ? $row['updated_at'] : null
            );
        }

        return new LanguageListResponseDTO(
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
