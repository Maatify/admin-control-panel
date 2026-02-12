<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Repository\I18n;

use Maatify\AdminKernel\Domain\DTO\Common\PaginationDTO;
use Maatify\AdminKernel\Domain\I18n\LanguageTranslationValue\DTO\LanguageTranslationValueListItemDTO;
use Maatify\AdminKernel\Domain\I18n\LanguageTranslationValue\DTO\LanguageTranslationValueListResponseDTO;
use Maatify\AdminKernel\Domain\I18n\LanguageTranslationValue\LanguageTranslationValueQueryReaderInterface;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\AdminKernel\Infrastructure\Query\ResolvedListFilters;
use PDO;
use RuntimeException;

use function is_string;
use function trim;

final readonly class PdoLanguageTranslationValueQueryReader implements LanguageTranslationValueQueryReaderInterface
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function queryTranslationValues(
        int $languageId,
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): LanguageTranslationValueListResponseDTO
    {
        $where  = [];
        $params = [
            'language_id' => $languageId,
        ];

        // ─────────────────────────────
        // Global search (scope OR domain OR key_part OR value)
        // ─────────────────────────────
        if ($filters->globalSearch !== null) {
            $g = trim($filters->globalSearch);
            if ($g !== '') {
                $where[] = '(
                    k.scope LIKE :global_text
                    OR k.domain LIKE :global_text
                    OR k.key_part LIKE :global_text
                    OR t.value LIKE :global_text
                )';
                $params['global_text'] = '%' . $g . '%';
            }
        }

        // ─────────────────────────────
        // Column filters
        // ─────────────────────────────
        foreach ($filters->columnFilters as $alias => $value) {

            if ($alias === 'id') {
                $where[] = 'k.id = :id';
                $params['id'] = (int)$value;
            }

            if ($alias === 'scope') {
                $where[] = 'k.scope LIKE :scope';
                $params['scope'] = '%' . trim((string)$value) . '%';
            }

            if ($alias === 'domain') {
                $where[] = 'k.domain LIKE :domain';
                $params['domain'] = '%' . trim((string)$value) . '%';
            }

            if ($alias === 'key_part') {
                $where[] = 'k.key_part LIKE :key_part';
                $params['key_part'] = '%' . trim((string)$value) . '%';
            }

            if ($alias === 'value') {
                $where[] = 't.value LIKE :value';
                $params['value'] = '%' . trim((string)$value) . '%';
            }
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // ─────────────────────────────
        // Total keys
        // ─────────────────────────────
        $stmtTotal = $this->pdo->query('SELECT COUNT(*) FROM i18n_keys');
        if ($stmtTotal === false) {
            throw new RuntimeException('Failed to execute total translation keys count query');
        }
        $total = (int)$stmtTotal->fetchColumn();

        // ─────────────────────────────
        // Filtered
        // ─────────────────────────────
        $stmtFiltered = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM i18n_keys k
            LEFT JOIN i18n_translations t
                ON t.key_id = k.id AND t.language_id = :language_id
            {$whereSql}
        ");

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
                k.scope,
                k.domain,
                k.key_part,
                t.id AS translation_id,
                t.language_id,
                t.value,
                COALESCE(t.created_at, k.created_at) AS created_at,
                t.updated_at
            FROM i18n_keys k
            LEFT JOIN i18n_translations t
                ON t.key_id = k.id AND t.language_id = :language_id
            {$whereSql}
            ORDER BY k.id ASC
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

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $items = [];

        foreach ($rows as $row) {

            $items[] = new LanguageTranslationValueListItemDTO(
                keyId: (int)$row['key_id'],
                scope: is_string($row['scope']) ? $row['scope'] : '',
                domain: is_string($row['domain']) ? $row['domain'] : '',
                keyPart: is_string($row['key_part']) ? $row['key_part'] : '',
                translationId: isset($row['translation_id']) ? (int)$row['translation_id'] : null,
                languageId: isset($row['language_id']) ? (int)$row['language_id'] : $languageId,
                value: is_string($row['value'] ?? null) ? $row['value'] : null,
                createdAt: is_string($row['created_at']) ? $row['created_at'] : '',
                updatedAt: is_string($row['updated_at'] ?? null) ? $row['updated_at'] : null
            );
        }

        return new LanguageTranslationValueListResponseDTO(
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

