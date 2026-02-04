<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Reader\I18n;

use Maatify\AdminKernel\Domain\DTO\Common\PaginationDTO;
use Maatify\AdminKernel\Domain\DTO\TranslationValueList\TranslationValueListItemDTO;
use Maatify\AdminKernel\Domain\DTO\TranslationValueList\TranslationValueListResponseDTO;
use Maatify\AdminKernel\Domain\I18n\Reader\TranslationValueQueryReaderInterface;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\AdminKernel\Infrastructure\Query\ResolvedListFilters;
use PDO;
use RuntimeException;

use function array_key_exists;
use function is_numeric;
use function is_string;
use function trim;

final readonly class PdoTranslationValueQueryReader implements TranslationValueQueryReaderInterface
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function queryTranslationValues(
        int $languageId,
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): TranslationValueListResponseDTO
    {
        $where  = [];
        $params = [
            'language_id' => $languageId,
        ];

        // ─────────────────────────────
        // Global search (key_name OR value)
        // ─────────────────────────────
        if ($filters->globalSearch !== null) {
            $g = trim($filters->globalSearch);
            if ($g !== '') {
                $where[] = '(k.key_name LIKE :global_text OR t.value LIKE :global_text)';
                $params['global_text'] = '%' . $g . '%';
            }
        }

        // ─────────────────────────────
        // Column filters (explicit only)
        // ─────────────────────────────
        foreach ($filters->columnFilters as $alias => $value) {
            if ($alias === 'id') {
                $where[] = 'k.id LIKE :id';
                $params['id'] = (int)$value;
            }
            if ($alias === 'key_name') {
                $where[] = 'k.key_name LIKE :key_name';
                $params['key_name'] = '%' . trim((string)$value) . '%';
            }

            if ($alias === 'value') {
                $where[] = 't.value LIKE :value';
                $params['value'] = '%' . trim((string)$value) . '%';
            }
        }

        $whereSql = $where ? 'WHERE ' . \implode(' AND ', $where) : '';

        // ─────────────────────────────
        // Total keys (independent of language)
        // ─────────────────────────────
        $stmtTotal = $this->pdo->query('SELECT COUNT(*) FROM i18n_keys');
        if ($stmtTotal === false) {
            throw new RuntimeException('Failed to execute total translation keys count query');
        }
        $total = (int)$stmtTotal->fetchColumn();

        // ─────────────────────────────
        // Filtered (based on search filters)
        // (language_id is fixed in LEFT JOIN condition)
        // ─────────────────────────────
        $stmtFiltered = $this->pdo->prepare(
            "
                SELECT COUNT(*)
                FROM i18n_keys k
                LEFT JOIN i18n_translations t
                    ON t.key_id = k.id AND t.language_id = :language_id
                {$whereSql}
            "
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
                k.id AS key_id,
                k.key_name,
                t.id AS translation_id,
                t.language_id,
                t.value,
                COALESCE(t.created_at, k.created_at) AS created_at,
                t.updated_at
            FROM i18n_keys k
            LEFT JOIN i18n_translations t
                ON t.key_id = k.id AND t.language_id = :language_id
            {$whereSql}
            ORDER BY
                k.id ASC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $k => $v) {
            if ($k === 'language_id') {
                $stmt->bindValue(':' . $k, (int)$v, PDO::PARAM_INT);
                continue;
            }
            $stmt->bindValue(':' . $k, $v);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $items = [];

        foreach ($rows as $row) {
            $keyIdRaw = $row['key_id'] ?? null;
            $keyId = is_numeric($keyIdRaw) ? (int)$keyIdRaw : 0;

            $translationId = null;
            if (array_key_exists('translation_id', $row) && $row['translation_id'] !== null) {
                $translationIdRaw = $row['translation_id'];
                $translationId = is_numeric($translationIdRaw) ? (int)$translationIdRaw : null;
            }

            $langRaw = $row['language_id'] ?? null;
            $langId = is_numeric($langRaw) ? (int)$langRaw : $languageId;

            $value = null;
            if (array_key_exists('value', $row) && is_string($row['value'])) {
                $value = $row['value'];
            }

            $items[] = new TranslationValueListItemDTO(
                keyId: $keyId,
                keyName: is_string($row['key_name']) ? $row['key_name'] : '',
                translationId: $translationId,
                languageId: $langId,
                value: $value,
                createdAt: is_string($row['created_at']) ? $row['created_at'] : '',
                updatedAt: is_string($row['updated_at'] ?? null) ? $row['updated_at'] : null
            );
        }

        return new TranslationValueListResponseDTO(
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

