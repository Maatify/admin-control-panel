<?php

declare(strict_types=1);

namespace Maatify\Currency\Infrastructure\Repository;

use Maatify\Currency\Contract\CurrencyQueryReaderInterface;
use Maatify\Currency\DTO\CurrencyDTO;
use Maatify\Currency\DTO\CurrencyTranslationDTO;
use Maatify\Currency\Exception\CurrencyPersistenceException;
use PDO;
use PDOStatement;

final class PdoCurrencyQueryReader implements CurrencyQueryReaderInterface
{
    /**
     * SELECT fragment shared by all translation queries.
     * Always aliased so CurrencyTranslationDTO::fromRow() gets consistent keys.
     */
    private const TRANSLATION_SELECT = '
        ct.id,
        l.id   AS language_id,
        l.code AS language_code,
        l.name AS language_name,
        ct.name,
        ct.created_at,
        ct.updated_at
    ';

    public function __construct(private readonly PDO $pdo) {}

    // ================================================================== //
    //  Admin list
    // ================================================================== //

    /**
     * {@inheritDoc}
     *
     * @param  array<string, int|string> $columnFilters
     * @return array{
     *     data:       list<CurrencyDTO>,
     *     pagination: array{
     *         page:     int,
     *         per_page: int,
     *         total:    int,
     *         filtered: int
     *     }
     * }
     */
    public function listCurrencies(
        int     $page,
        int     $perPage,
        ?string $globalSearch,
        array   $columnFilters,
        ?int    $languageId = null,
    ): array {
        $page   = max(1, $page);
        $limit  = max(1, min(200, $perPage));
        $offset = ($page - 1) * $limit;

        $where  = [];
        $params = [];

        // ── Global search (code + name only — base table columns) ──
        if ($globalSearch !== null && trim($globalSearch) !== '') {
            $where[]               = '(c.`code` LIKE :global_text OR c.`name` LIKE :global_text)';
            $params['global_text'] = '%' . $this->escapeLike(trim($globalSearch)) . '%';
        }

        // ── Column filters ─────────────────────────────────────────
        if (isset($columnFilters['is_active'])) {
            $where[]               = 'c.`is_active` = :is_active';
            $params['is_active']   = (int) $columnFilters['is_active'];
        }

        if (isset($columnFilters['code'])) {
            $where[]         = 'c.`code` = :code';
            $params['code']  = strtoupper(trim((string) $columnFilters['code']));
        }

        if (isset($columnFilters['name'])) {
            $where[]        = 'c.`name` LIKE :name';
            $params['name'] = '%' . $this->escapeLike(trim((string) $columnFilters['name'])) . '%';
        }

        if (isset($columnFilters['id'])) {
            $where[]      = 'c.`id` = :id';
            $params['id'] = (int) $columnFilters['id'];
        }

        $whereSql = $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '';

        // ── Translation JOIN (optional — for display_name only) ────
        [$selectExtra, $join, $joinParams] = $this->buildTranslationJoin($languageId);

        // ── Total (unfiltered) ─────────────────────────────────────
        $total = $this->scalarInt('SELECT COUNT(*) FROM `currencies`');

        // ── Filtered count ─────────────────────────────────────────
        $stmtFiltered = $this->prepareOrFail(
            "SELECT COUNT(*) FROM `currencies` AS c {$whereSql}",
        );
        foreach ($params as $key => $value) {
            $stmtFiltered->bindValue(':' . $key, $value);
        }
        $stmtFiltered->execute();
        $filtered = (int) $stmtFiltered->fetchColumn();

        // ── Data page ──────────────────────────────────────────────
        // Translation JOIN uses positional ?, filters use :named params.
        // Bind join params first (positional), then named filter + pagination params.
        $stmt = $this->prepareOrFail("
            SELECT c.*, {$selectExtra}
            FROM   `currencies` AS c
            {$join}
            {$whereSql}
            ORDER BY c.`display_order` ASC, c.`id` ASC
            LIMIT :limit OFFSET :offset
        ");

        $pos = 1;
        foreach ($joinParams as $v) {
            $stmt->bindValue($pos++, $v, PDO::PARAM_INT);
        }
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        /** @var list<CurrencyDTO> $data */
        $data = array_map(
            static fn (array $row): CurrencyDTO => CurrencyDTO::fromRow($row),
            $this->fetchAllAssoc($stmt),
        );

        return [
            'data'       => $data,
            'pagination' => [
                'page'     => $page,
                'per_page' => $limit,
                'total'    => $total,
                'filtered' => $filtered,
            ],
        ];
    }

    // ================================================================== //
    //  Website list
    // ================================================================== //

    /**
     * {@inheritDoc}
     *
     * @return list<CurrencyDTO>
     */
    public function listActiveCurrencies(?int $languageId = null): array
    {
        [$selectExtra, $join, $joinParams] = $this->buildTranslationJoin($languageId);

        $sql = "
            SELECT c.*, {$selectExtra}
            FROM   `currencies` AS c
            {$join}
            WHERE  c.`is_active` = 1
            ORDER BY c.`display_order` ASC, c.`id` ASC
        ";

        $stmt = $this->prepareOrFail($sql);
        $pos  = 1;
        foreach ($joinParams as $v) {
            $stmt->bindValue($pos++, $v, PDO::PARAM_INT);
        }
        $stmt->execute();

        /** @var list<CurrencyDTO> $result */
        $result = array_map(
            static fn (array $row): CurrencyDTO => CurrencyDTO::fromRow($row),
            $this->fetchAllAssoc($stmt),
        );

        return $result;
    }

    // ================================================================== //
    //  Single-record lookups
    // ================================================================== //

    /** {@inheritDoc} */
    public function findById(int $id, ?int $languageId = null): ?CurrencyDTO
    {
        [$selectExtra, $join, $joinParams] = $this->buildTranslationJoin($languageId);

        $sql = "
            SELECT c.*, {$selectExtra}
            FROM   `currencies` AS c
            {$join}
            WHERE  c.`id` = ?
            LIMIT  1
        ";

        $stmt = $this->prepareOrFail($sql);
        $pos  = 1;
        foreach ($joinParams as $v) {
            $stmt->bindValue($pos++, $v, PDO::PARAM_INT);
        }
        $stmt->bindValue($pos, $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $this->fetchAssoc($stmt);

        return $row !== null ? CurrencyDTO::fromRow($row) : null;
    }

    /** {@inheritDoc} */
    public function findByCode(string $code, ?int $languageId = null): ?CurrencyDTO
    {
        [$selectExtra, $join, $joinParams] = $this->buildTranslationJoin($languageId);

        $sql = "
            SELECT c.*, {$selectExtra}
            FROM   `currencies` AS c
            {$join}
            WHERE  c.`code` = ?
            LIMIT  1
        ";

        $stmt = $this->prepareOrFail($sql);
        $pos  = 1;
        foreach ($joinParams as $v) {
            $stmt->bindValue($pos++, $v, PDO::PARAM_INT);
        }
        $stmt->bindValue($pos, strtoupper($code));
        $stmt->execute();

        $row = $this->fetchAssoc($stmt);

        return $row !== null ? CurrencyDTO::fromRow($row) : null;
    }

    // ================================================================== //
    //  Translation management — single lookup
    // ================================================================== //

    /**
     * {@inheritDoc}
     *
     * INNER JOIN — only returns a DTO when the translation row exists.
     */
    public function findTranslation(int $currencyId, int $languageId): ?CurrencyTranslationDTO
    {
        $stmt = $this->prepareOrFail(
            'SELECT ' . self::TRANSLATION_SELECT . '
             FROM `currency_translations` ct
             INNER JOIN `languages` l ON l.id = ct.`language_id`
             WHERE ct.`currency_id` = ? AND ct.`language_id` = ?
             LIMIT 1',
        );
        $stmt->execute([$currencyId, $languageId]);

        $row = $this->fetchAssoc($stmt);

        return $row !== null ? CurrencyTranslationDTO::fromRow($row) : null;
    }

    /**
     * {@inheritDoc}
     *
     * @param  array<string, int|string> $columnFilters
     * @return array{
     *     data:       list<CurrencyTranslationDTO>,
     *     pagination: array{
     *         page:     int,
     *         per_page: int,
     *         total:    int,
     *         filtered: int
     *     }
     * }
     */
    public function listTranslationsForCurrencyPaginated(
        int     $currencyId,
        int     $page,
        int     $perPage,
        ?string $globalSearch,
        array   $columnFilters,
    ): array {
        $page   = max(1, $page);
        $limit  = max(1, min(200, $perPage));
        $offset = ($page - 1) * $limit;

        $where  = [];
        $params = [];

        // ── Always scope to active languages ──────────────────────
        $where[] = 'l.`is_active` = 1';

        // ── Global search ──────────────────────────────────────────
        if ($globalSearch !== null && trim($globalSearch) !== '') {
            $where[]               = '(l.`name` LIKE :global_text OR l.`code` LIKE :global_text OR ct.`name` LIKE :global_text)';
            $params['global_text'] = '%' . $this->escapeLike(trim($globalSearch)) . '%';
        }

        // ── Column filters ─────────────────────────────────────────
        if (isset($columnFilters['language_id'])) {
            $where[]               = 'l.`id` = :language_id';
            $params['language_id'] = (int) $columnFilters['language_id'];
        }

        if (isset($columnFilters['language_code'])) {
            $where[]                = 'l.`code` LIKE :language_code';
            $params['language_code'] = '%' . $this->escapeLike((string) $columnFilters['language_code']) . '%';
        }

        if (isset($columnFilters['language_name'])) {
            $where[]                = 'l.`name` LIKE :language_name';
            $params['language_name'] = '%' . $this->escapeLike((string) $columnFilters['language_name']) . '%';
        }

        if (isset($columnFilters['name'])) {
            $where[]             = 'ct.`name` LIKE :trans_name';
            $params['trans_name'] = '%' . $this->escapeLike((string) $columnFilters['name']) . '%';
        }

        // has_translation: '1' = row exists, '0' = no row yet
        // Checks ct.id IS NOT NULL — consistent with CurrencyTranslationDTO::hasTranslation()
        if (isset($columnFilters['has_translation'])) {
            $val = (string) $columnFilters['has_translation'];
            if ($val === '1') {
                $where[] = 'ct.`id` IS NOT NULL';
            } elseif ($val === '0') {
                $where[] = 'ct.`id` IS NULL';
            }
        }

        $whereSql = 'WHERE ' . implode(' AND ', $where);

        // ── Total (unfiltered — all active languages) ──────────────
        $stmtTotal = $this->prepareOrFail('
            SELECT COUNT(*)
            FROM `languages` l
            LEFT JOIN `currency_translations` ct
                   ON ct.`language_id` = l.`id`
                  AND ct.`currency_id` = :currency_id
            WHERE l.`is_active` = 1
        ');
        $stmtTotal->execute([':currency_id' => $currencyId]);
        $total = (int) $stmtTotal->fetchColumn();

        // ── Filtered count ─────────────────────────────────────────
        $stmtFiltered = $this->prepareOrFail("
            SELECT COUNT(*)
            FROM `languages` l
            LEFT JOIN `currency_translations` ct
                   ON ct.`language_id` = l.`id`
                  AND ct.`currency_id` = :currency_id
            {$whereSql}
        ");
        $stmtFiltered->execute(array_merge([':currency_id' => $currencyId], $params));
        $filtered = (int) $stmtFiltered->fetchColumn();

        // ── Data page ──────────────────────────────────────────────
        $stmt = $this->prepareOrFail('
            SELECT ' . self::TRANSLATION_SELECT . "
            FROM `languages` l
            LEFT JOIN `currency_translations` ct
                   ON ct.`language_id` = l.`id`
                  AND ct.`currency_id` = :currency_id
            {$whereSql}
            ORDER BY l.`id` ASC
            LIMIT :limit OFFSET :offset
        ");

        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':currency_id', $currencyId, PDO::PARAM_INT);
        $stmt->bindValue(':limit',       $limit,      PDO::PARAM_INT);
        $stmt->bindValue(':offset',      $offset,     PDO::PARAM_INT);
        $stmt->execute();

        /** @var list<CurrencyTranslationDTO> $data */
        $data = array_map(
            static fn (array $row): CurrencyTranslationDTO => CurrencyTranslationDTO::fromRow($row),
            $this->fetchAllAssoc($stmt),
        );

        return [
            'data'       => $data,
            'pagination' => [
                'page'     => $page,
                'per_page' => $limit,
                'total'    => $total,
                'filtered' => $filtered,
            ],
        ];
    }

    // ================================================================== //
    //  Aggregates
    // ================================================================== //

    /** {@inheritDoc} */
    public function maxDisplayOrder(): int
    {
        return $this->scalarInt(
            'SELECT COALESCE(MAX(`display_order`), 0) FROM `currencies`',
        );
    }

    // ================================================================== //
    //  Private — currency list JOIN builder
    // ================================================================== //

    /**
     * Builds the SELECT fragment + LEFT JOIN for the currency display name.
     *
     * @return array{0: string, 1: string, 2: list<int>}
     *   [selectExtra, joinClause, bindParams]
     */
    private function buildTranslationJoin(?int $languageId): array
    {
        if ($languageId === null) {
            return [
                'NULL AS `translated_name`, NULL AS `translation_language_id`',
                '',
                [],
            ];
        }

        return [
            'COALESCE(ct.`name`, c.`name`) AS `translated_name`, ? AS `translation_language_id`',
            'LEFT JOIN `currency_translations` ct
                    ON ct.`currency_id` = c.`id` AND ct.`language_id` = ?',
            [$languageId, $languageId],
        ];
    }

    // ================================================================== //
    //  Private — shared utilities
    // ================================================================== //

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    private function prepareOrFail(string $sql): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        if ($stmt === false) {
            throw CurrencyPersistenceException::prepareFailed($sql);
        }

        return $stmt;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchAssoc(PDOStatement $stmt): ?array
    {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false || !is_array($row)) {
            return null;
        }

        /** @var array<string, mixed> $row */
        return $row;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchAllAssoc(PDOStatement $stmt): array
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }

    /**
     * @param list<int|string> $params
     */
    private function scalarInt(string $sql, array $params = []): int
    {
        $stmt = $this->prepareOrFail($sql);
        $stmt->execute($params);
        $val = $stmt->fetchColumn();

        return is_numeric($val) ? (int) $val : 0;
    }
}
