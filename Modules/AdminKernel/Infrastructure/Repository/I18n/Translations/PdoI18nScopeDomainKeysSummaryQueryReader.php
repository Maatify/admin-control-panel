<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-13 01:24
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Repository\I18n\Translations;

use Maatify\AdminKernel\Domain\DTO\Common\PaginationDTO;
use Maatify\AdminKernel\Domain\I18n\Translations\DTO\I18nScopeDomainKeysSummaryListItemDTO;
use Maatify\AdminKernel\Domain\I18n\Translations\DTO\I18nScopeDomainKeysSummaryListResponseDTO;
use Maatify\AdminKernel\Domain\I18n\Translations\I18nScopeDomainKeysSummaryQueryReaderInterface;
use Maatify\AdminKernel\Domain\List\Filters\ResolvedListFilters;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use PDO;
use RuntimeException;

final readonly class PdoI18nScopeDomainKeysSummaryQueryReader
    implements I18nScopeDomainKeysSummaryQueryReaderInterface
{
    public function __construct(
        private PDO $pdo
    ) {}

    public function query(
        string $scopeCode,
        string $domainCode,
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): I18nScopeDomainKeysSummaryListResponseDTO {

        // ─────────────────────────────
        // Base filters (always enforced)
        // ─────────────────────────────
        $params = [
            'scope'  => $scopeCode,
            'domain' => $domainCode,
        ];

        $where = [
            'k.scope = :scope',
            'k.domain = :domain',
        ];

        $having = [];

        // ─────────────────────────────
        // Language subset filters (explicit only)
        // ─────────────────────────────
        $langWhere = [];
        $langParams = [];

        // GLOBAL SEARCH
        if ($filters->globalSearch !== null) {
            $g = trim($filters->globalSearch);
            if ($g !== '') {
                $where[] = '(k.key_part LIKE :search OR k.description LIKE :search)';
                $params['search'] = '%' . $g . '%';
            }
        }

        foreach ($filters->columnFilters as $alias => $value) {
            switch ($alias) {

                case 'key_id':
                    $where[] = 'k.id = :key_id';
                    $params['key_id'] = (int)$value;
                    break;

                case 'key_part':
                    $where[] = 'k.key_part = :key_part';
                    $params['key_part'] = trim((string)$value);
                    break;

                case 'missing':
                    if ((int)$value === 1) {
                        $having[] = 'missing_count > 0';
                    }
                    break;

                case 'language_id':
                    $languageId = (int)$value;
                    if ($languageId > 0) {
                        $langWhere[] = 'l.id = :language_id';
                        $langParams['language_id'] = $languageId;
                    }
                    break;

                case 'language_is_active':
                    // Explicit-only:
                    // - if sent as 1 => active only
                    // - if sent as 0 => all (no condition)
                    if ((int)$value === 1) {
                        $langWhere[] = 'l.is_active = 1';
                    }
                    break;
            }
        }

        $whereSql  = 'WHERE ' . implode(' AND ', $where);
        $havingSql = $having ? 'HAVING ' . implode(' AND ', $having) : '';

        $langWhereSql = $langWhere ? ('WHERE ' . implode(' AND ', $langWhere)) : '';

        // TOTAL (no filters)
        $stmtTotal = $this->pdo->prepare(
            "SELECT COUNT(*) FROM i18n_keys k WHERE k.scope = :scope AND k.domain = :domain"
        );

        if ($stmtTotal === false) {
            throw new RuntimeException('Failed to prepare total i18n keys query');
        }

        $stmtTotal->execute([
            'scope'  => $scopeCode,
            'domain' => $domainCode,
        ]);

        $total = (int)$stmtTotal->fetchColumn();

        // ─────────────────────────────
        // Shared computed layer:
        // - total_languages = COUNT(languages subset)
        // - translated_count = COUNT(translations joined to same subset)
        // ─────────────────────────────
        $baseSelect = "
            SELECT
                k.id,
                k.key_part,
                k.description,
                al.total_languages AS total_languages,
                (al.total_languages - COALESCE(ta.translated_count, 0)) AS missing_count
            FROM i18n_keys k
            CROSS JOIN (
                SELECT COUNT(*) AS total_languages
                FROM languages l
                {$langWhereSql}
            ) al
            LEFT JOIN (
                SELECT
                    t.key_id,
                    COUNT(*) AS translated_count
                FROM i18n_translations t
                JOIN languages l
                    ON l.id = t.language_id
                " . ($langWhere ? ('WHERE ' . implode(' AND ', $langWhere)) : '') . "
                GROUP BY t.key_id
            ) ta
                ON ta.key_id = k.id
            {$whereSql}
        ";

        // FILTERED
        $filteredSql = "
            SELECT COUNT(*) FROM (
                {$baseSelect}
                {$havingSql}
            ) x
        ";

        $stmtFiltered = $this->pdo->prepare($filteredSql);

        if ($stmtFiltered === false) {
            throw new RuntimeException('Failed to prepare filtered i18n keys query');
        }

        foreach ($params as $k => $v) {
            $stmtFiltered->bindValue(':' . $k, $v);
        }
        foreach ($langParams as $k => $v) {
            $stmtFiltered->bindValue(':' . $k, $v, PDO::PARAM_INT);
        }

        $stmtFiltered->execute();
        $filtered = (int)$stmtFiltered->fetchColumn();

        // DATA
        $limit  = $query->perPage;
        $offset = ($query->page - 1) * $limit;

        $dataSql = "
            {$baseSelect}
            {$havingSql}
            ORDER BY key_part ASC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->pdo->prepare($dataSql);

        if ($stmt === false) {
            throw new RuntimeException('Failed to prepare i18n translations list query');
        }

        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }
        foreach ($langParams as $k => $v) {
            $stmt->bindValue(':' . $k, $v, PDO::PARAM_INT);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        /**
         * @var array<int, array{
         *   id: numeric-string|int,
         *   key_part: string,
         *   description: string|null,
         *   total_languages: numeric-string|int,
         *   missing_count: numeric-string|int
         * }> $rows
         */
        $items = [];
        foreach ($rows as $row) {
            $items[] = new I18nScopeDomainKeysSummaryListItemDTO(
                id: (int)$row['id'],
                keyPart: (string)$row['key_part'],
                description: $row['description'] !== null ? (string)$row['description'] : null,
                totalLanguages: (int)$row['total_languages'],
                missingCount: (int)$row['missing_count'],
            );
        }

        return new I18nScopeDomainKeysSummaryListResponseDTO(
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
