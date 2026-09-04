<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-23 19:33
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\ContentDocuments;

use Maatify\AdminKernel\Domain\ContentDocuments\ContentDocumentTranslationQueryReaderInterface;
use Maatify\AdminKernel\Domain\ContentDocuments\DTO\ContentDocumentTranslationListItemDTO;
use Maatify\AdminKernel\Domain\ContentDocuments\DTO\ContentDocumentTranslationListResponseDTO;
use Maatify\AdminKernel\Domain\DTO\Common\PaginationDTO;
use Maatify\AdminKernel\Domain\List\Filters\ResolvedListFilters;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use PDO;

use function trim;

final readonly class PdoContentDocumentTranslationQueryReader
    implements ContentDocumentTranslationQueryReaderInterface
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function query(
        int $documentId,
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): ContentDocumentTranslationListResponseDTO {

        $where = [];
        $params = [
            'document_id' => $documentId,
        ];

        // ─────────────────────────────
        // Global Search
        // ─────────────────────────────
        if ($filters->globalSearch !== null) {
            $g = trim($filters->globalSearch);
            if ($g !== '') {
                $where[] = '(
                    l.code LIKE :global_text
                    OR l.name LIKE :global_text
                )';
                $params['global_text'] = '%' . $g . '%';
            }
        }

        // ─────────────────────────────
        // Column Filters
        // ─────────────────────────────
        foreach ($filters->columnFilters as $alias => $value) {

            if ($alias === 'language_id') {
                $where[] = 'l.id = :language_id';
                $params['language_id'] = (int)$value;
            }

            if ($alias === 'has_translation') {
                if ((int)$value === 1) {
                    $where[] = 't.id IS NOT NULL';
                } else {
                    $where[] = 't.id IS NULL';
                }
            }
        }

        $whereSql = '';
        if ($where !== []) {
            $whereSql = 'WHERE ' . implode(' AND ', $where);
        }

        // ─────────────────────────────
        // Total (all languages)
        // ─────────────────────────────
        $stmtTotal = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM languages
        ");

        $stmtTotal->execute();
        $total = (int)$stmtTotal->fetchColumn();

        // ─────────────────────────────
        // Filtered
        // ─────────────────────────────
        $stmtFiltered = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM languages l
            LEFT JOIN language_settings ls
                ON ls.language_id = l.id
            LEFT JOIN document_translations t
                ON t.language_id = l.id
               AND t.document_id = :document_id
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
                t.id AS translation_id,
                l.id AS language_id,
                l.code AS language_code,
                l.name AS language_name,
                ls.icon AS language_icon,
                ls.direction AS language_direction,
                t.updated_at
            FROM languages l
            LEFT JOIN language_settings ls
                ON ls.language_id = l.id
            LEFT JOIN document_translations t
                ON t.language_id = l.id
               AND t.document_id = :document_id
            {$whereSql}
            ORDER BY l.code ASC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $items = [];

        foreach ($rows as $row) {
            $items[] = new ContentDocumentTranslationListItemDTO(
                document_id        : $documentId,
                language_id        : (int)$row['language_id'],
                language_code      : (string)$row['language_code'],
                language_name      : (string)$row['language_name'],
                language_icon      : $row['language_icon'] ?? null,
                language_direction : $row['language_direction'] ?? null,
                has_translation    : $row['translation_id'] !== null,
                translation_id     : isset($row['translation_id'])
                    ? (int)$row['translation_id']
                    : null,
                updated_at         : $row['updated_at'] ?? null,
            );
        }

        return new ContentDocumentTranslationListResponseDTO(
            data       : $items,
            pagination : new PaginationDTO(
                page     : $query->page,
                perPage  : $query->perPage,
                total    : $total,
                filtered : $filtered
            )
        );
    }
}