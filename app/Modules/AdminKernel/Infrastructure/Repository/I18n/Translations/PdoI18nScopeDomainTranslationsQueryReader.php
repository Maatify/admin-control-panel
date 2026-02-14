<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-13 20:07
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Repository\I18n\Translations;

use Maatify\AdminKernel\Domain\DTO\Common\PaginationDTO;
use Maatify\AdminKernel\Domain\I18n\Translations\DTO\I18nScopeDomainTranslationsListItemDTO;
use Maatify\AdminKernel\Domain\I18n\Translations\DTO\I18nScopeDomainTranslationsListResponseDTO;
use Maatify\AdminKernel\Domain\I18n\Translations\I18nScopeDomainTranslationsQueryReaderInterface;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\AdminKernel\Infrastructure\Query\ResolvedListFilters;
use PDO;

use function trim;

final readonly class PdoI18nScopeDomainTranslationsQueryReader
    implements I18nScopeDomainTranslationsQueryReaderInterface
{
    public function __construct(
        private PDO $pdo
    )
    {
    }

    public function queryScopeDomainTranslations(
        string $scopeCode,
        string $domainCode,
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): I18nScopeDomainTranslationsListResponseDTO
    {
        $where = [
            'k.scope = :scope_code',
            'k.domain = :domain_code',
        ];

        $params = [
            'scope_code'  => $scopeCode,
            'domain_code' => $domainCode,
        ];

        // ─────────────────────────────
        // Global Search
        // ─────────────────────────────
        if ($filters->globalSearch !== null) {
            $g = trim($filters->globalSearch);
            if ($g !== '') {
                $where[] = '(
                    k.key_part LIKE :global_text
                    OR k.description LIKE :global_text
                    OR t.value LIKE :global_text
                    OR l.code LIKE :global_text
                    OR l.name LIKE :global_text
                )';
                $params['global_text'] = '%' . $g . '%';
            }
        }

        // ─────────────────────────────
        // Column Filters
        // ─────────────────────────────
        foreach ($filters->columnFilters as $alias => $value) {
            if ($alias === 'key_id') {
                $where[] = 'k.id = :key_id';
                $params['key_id'] = (int)$value;
            }

            if ($alias === 'key_part') {
                $where[] = 'k.key_part LIKE :key_part';
                $params['key_part'] = '%' . trim((string)$value) . '%';
            }

            // مهم: الفلتر على l.id
            if ($alias === 'language_id') {
                $where[] = 'l.id = :language_id';
                $params['language_id'] = (int)$value;
            }

            if ($alias === 'value') {
                $where[] = 't.value LIKE :value';
                $params['value'] = '%' . trim((string)$value) . '%';
            }
        }

        $whereSql = 'WHERE ' . implode(' AND ', $where);

        // ─────────────────────────────
        // Total (keys × languages)
        // ─────────────────────────────
        $stmtTotal = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM i18n_keys k
            CROSS JOIN languages l
            WHERE k.scope = :scope_code
              AND k.domain = :domain_code
        ");

        $stmtTotal->execute([
            'scope_code'  => $scopeCode,
            'domain_code' => $domainCode,
        ]);

        $total = (int)$stmtTotal->fetchColumn();

        // ─────────────────────────────
        // Filtered
        // ─────────────────────────────
        $stmtFiltered = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM i18n_keys k
            CROSS JOIN languages l
            LEFT JOIN language_settings ls
                ON ls.language_id = l.id
            LEFT JOIN i18n_translations t
                ON t.key_id = k.id
                AND t.language_id = l.id
            {$whereSql}
        ");

        $stmtFiltered->execute($params);
        $filtered = (int)$stmtFiltered->fetchColumn();

        // ─────────────────────────────
        // Data
        // ─────────────────────────────
        $limit = $query->perPage;
        $offset = ($query->page - 1) * $limit;

        $sql = "
            SELECT
                t.id AS id,
                k.id AS key_id,
                k.key_part,
                k.description,
                l.id AS language_id,
                l.code AS language_code,
                l.name AS language_name,
                ls.icon AS language_icon,
                ls.direction AS language_direction,
                t.value
            FROM i18n_keys k
            CROSS JOIN languages l
            LEFT JOIN language_settings ls
                ON ls.language_id = l.id
            LEFT JOIN i18n_translations t
                ON t.key_id = k.id
                AND t.language_id = l.id
            {$whereSql}
            ORDER BY k.key_part ASC, l.code ASC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ? : [];

        $items = [];

        foreach ($rows as $row) {
            $items[] = new I18nScopeDomainTranslationsListItemDTO(
                id               : isset($row['id']) ? (int)$row['id'] : null,
                keyId            : (int)$row['key_id'],
                keyPart          : (string)$row['key_part'],
                description      : $row['description'] ?? null,
                languageId       : (int)$row['language_id'],
                languageCode     : (string)$row['language_code'],
                languageName     : (string)$row['language_name'],
                languageIcon     : $row['language_icon'] ?? null,
                languageDirection: $row['language_direction'] ?? null,
                value            : $row['value'] ?? null,
            );
        }

        return new I18nScopeDomainTranslationsListResponseDTO(
            data      : $items,
            pagination: new PaginationDTO(
                page    : $query->page,
                perPage : $query->perPage,
                total   : $total,
                filtered: $filtered
            )
        );
    }
}
