<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-11 02:27
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Repository\I18n\Keys;

use Maatify\AdminKernel\Domain\DTO\Common\PaginationDTO;
use Maatify\AdminKernel\Domain\Exception\EntityNotFoundException;
use Maatify\AdminKernel\Domain\I18n\Keys\DTO\I18nScopeKeyListItemDTO;
use Maatify\AdminKernel\Domain\I18n\Keys\DTO\I18nScopeKeysListResponseDTO;
use Maatify\AdminKernel\Domain\I18n\Keys\I18nScopeKeysQueryReaderInterface;
use Maatify\AdminKernel\Domain\List\Filters\ResolvedListFilters;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use PDO;
use RuntimeException;

final readonly class PdoI18nScopeKeysQueryReader
    implements I18nScopeKeysQueryReaderInterface
{
    public function __construct(private PDO $pdo) {}

    public function queryScopeKeys(
        int $scopeId,
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): I18nScopeKeysListResponseDTO {
        $scopeCode = $this->resolveScopeCode($scopeId);
        // ─────────────────────────────
        // Global search (free text: key_name OR description)
        // ─────────────────────────────
        if ($filters->globalSearch !== null) {
            $g = trim($filters->globalSearch);

            if ($g !== '') {
                $where[] = '(k.domain LIKE :global_text OR k.key_part LIKE :global_text)';
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

            if ($alias === 'domain') {
                $where[] = 'k.domain LIKE :domain';
                $params['domain'] = '%' . trim((string)$value) . '%';
            }

            if ($alias === 'key_part') {
                $where[] = 'k.key_part LIKE :key_part';
                $params['key_part'] = '%' . trim((string)$value) . '%';
            }
        }

        $where[] = 'k.scope = :scope';
        $params['scope'] = $scopeCode;

        $whereSql = 'WHERE ' . implode(' AND ', $where);


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
                k.scope,
                k.domain,
                k.key_part,
                k.description,
                k.created_at
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
            $items[] = new I18nScopeKeyListItemDTO(
                (int)$row['id'],
                $row['scope'],
                $row['domain'],
                $row['key_part'],
                $row['description'],
                $row['created_at']
            );
        }


        return new I18nScopeKeysListResponseDTO(
            $items,
            pagination: new PaginationDTO(
                page: $query->page,
                perPage: $query->perPage,
                total: $total,
                filtered: $filtered
            )
        );
    }

    private function resolveScopeCode(int $scopeId): string
    {
        $stmt = $this->pdo->prepare(
            'SELECT code FROM i18n_scopes WHERE id = :id'
        );
        $stmt->execute(['id' => $scopeId]);

        $code = $stmt->fetchColumn();
        if (!$code) {
            throw new EntityNotFoundException('scope not found', 'scopeId');
        }

        return (string)$code;
    }
}

