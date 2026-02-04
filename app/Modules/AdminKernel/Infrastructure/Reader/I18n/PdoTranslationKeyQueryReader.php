<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Reader\I18n;

use Maatify\AdminKernel\Domain\DTO\Common\PaginationDTO;
use Maatify\AdminKernel\Domain\DTO\TranslationKeyList\TranslationKeyListItemDTO;
use Maatify\AdminKernel\Domain\DTO\TranslationKeyList\TranslationKeyListResponseDTO;
use Maatify\AdminKernel\Domain\I18n\Reader\TranslationKeyQueryReaderInterface;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\AdminKernel\Infrastructure\Query\ResolvedListFilters;
use PDO;
use RuntimeException;

final readonly class PdoTranslationKeyQueryReader implements TranslationKeyQueryReaderInterface
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function queryTranslationKeys(
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): TranslationKeyListResponseDTO
    {
        $where  = [];
        $params = [];

        // ─────────────────────────────
        // Global search (free text: key_name OR description)
        // ─────────────────────────────
        if ($filters->globalSearch !== null) {
            $g = trim($filters->globalSearch);

            if ($g !== '') {
                $where[] = '(k.key_name LIKE :global_text OR k.description LIKE :global_text)';
                $params['global_text'] = '%' . $g . '%';
            }
        }

        // ─────────────────────────────
        // Column filters (explicit only)
        // ─────────────────────────────
        foreach ($filters->columnFilters as $alias => $value) {

            if ($alias === 'id') {
                $where[] = 'k.id = :id';
                $params['id'] = (int)$value;
            }

            if ($alias === 'key_name') {
                $where[] = 'k.key_name LIKE :key_name';
                $params['key_name'] = '%' . trim((string)$value) . '%';
            }

            if ($alias === 'description') {
                $where[] = 'k.description LIKE :description';
                $params['description'] = '%' . trim((string)$value) . '%';
            }
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // ─────────────────────────────
        // Total (no filters)
        // ─────────────────────────────
        $stmtTotal = $this->pdo->query('SELECT COUNT(*) FROM i18n_keys');

        if ($stmtTotal === false) {
            throw new RuntimeException('Failed to execute total translation keys count query');
        }

        $total = (int)$stmtTotal->fetchColumn();

        // ─────────────────────────────
        // Filtered
        // ─────────────────────────────
        $stmtFiltered = $this->pdo->prepare(
            "SELECT COUNT(*) FROM i18n_keys k {$whereSql}"
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
                k.id,
                k.key_name,
                k.description,
                k.created_at,
                k.updated_at
            FROM i18n_keys k
            {$whereSql}
            ORDER BY
                k.id ASC
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
            $items[] = new TranslationKeyListItemDTO(
                id: (int)$row['id'],
                keyName: (string)$row['key_name'],
                description: is_string($row['description'] ?? null) ? $row['description'] : null,
                createdAt: (string)$row['created_at'],
                updatedAt: is_string($row['updated_at'] ?? null) ? $row['updated_at'] : null
            );
        }

        return new TranslationKeyListResponseDTO(
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
