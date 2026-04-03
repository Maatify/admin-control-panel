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
    /** Columns that callers are allowed to filter on directly (currency list). */
    private const ALLOWED_FILTERS = ['is_active', 'code'];

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
        $page    = max(1, $page);
        $perPage = max(1, min(200, $perPage));
        $offset  = ($page - 1) * $perPage;

        [$where, $filterParams] = $this->buildWhereClause($globalSearch, $columnFilters);
        [$selectExtra, $join, $joinParams] = $this->buildTranslationJoin($languageId);

        $total = $this->scalarInt('SELECT COUNT(*) FROM `currencies`');

        $filtered = $this->scalarInt(
            "SELECT COUNT(*) FROM `currencies` AS c {$where}",
            $filterParams,
        );

        // NOTE: positional ? only — PDO forbids mixing ? and :name in one statement.
        $sql = "
            SELECT c.*, {$selectExtra}
            FROM   `currencies` AS c
            {$join}
            {$where}
            ORDER BY c.`display_order` ASC, c.`id` ASC
            LIMIT ? OFFSET ?
        ";

        $stmt = $this->prepareOrFail($sql);

        $pos = 1;
        foreach ($joinParams as $v) {
            $stmt->bindValue($pos++, $v, PDO::PARAM_INT);
        }
        foreach ($filterParams as $v) {
            $stmt->bindValue($pos++, $v);
        }
        $stmt->bindValue($pos++, $perPage, PDO::PARAM_INT);
        $stmt->bindValue($pos,   $offset,  PDO::PARAM_INT);
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
                'per_page' => $perPage,
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

    // ================================================================== //
    //  Translation management — full listing (all languages)
    // ================================================================== //

    /**
     * {@inheritDoc}
     *
     * LEFT JOIN `languages` — shows every active language, including those
     * without a translation row (translatedName = null for those).
     *
     * @return list<CurrencyTranslationDTO>
     */
    public function listTranslationsForCurrency(int $currencyId): array
    {
        $stmt = $this->prepareOrFail(
            'SELECT ' . self::TRANSLATION_SELECT . '
             FROM `languages` l
             LEFT JOIN `currency_translations` ct
                    ON ct.`language_id` = l.`id`
                   AND ct.`currency_id` = ?
             WHERE l.`is_active` = 1
             ORDER BY l.`id` ASC',
        );
        $stmt->execute([$currencyId]);

        /** @var list<CurrencyTranslationDTO> $result */
        $result = array_map(
            static fn (array $row): CurrencyTranslationDTO => CurrencyTranslationDTO::fromRow($row),
            $this->fetchAllAssoc($stmt),
        );

        return $result;
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
        $page    = max(1, $page);
        $perPage = max(1, min(200, $perPage));
        $offset  = ($page - 1) * $perPage;

        [$where, $params] = $this->buildTranslationWhereClause(
            $currencyId,
            $globalSearch,
            $columnFilters,
        );

        // Base JOIN used by all three queries
        $baseFrom = '
            FROM `languages` l
            LEFT JOIN `currency_translations` ct
                   ON ct.`language_id` = l.`id`
                  AND ct.`currency_id` = :currency_id
        ';

        // Total = all active languages for this currency (unfiltered)
        $stmtTotal = $this->prepareOrFail(
            "SELECT COUNT(*) {$baseFrom} WHERE l.`is_active` = 1",
        );
        $stmtTotal->execute([':currency_id' => $currencyId]);
        $total = (int) $stmtTotal->fetchColumn();

        // Filtered count
        $stmtFiltered = $this->prepareOrFail(
            "SELECT COUNT(*) {$baseFrom} {$where}",
        );
        $stmtFiltered->execute($params);
        $filtered = (int) $stmtFiltered->fetchColumn();

        // Data page
        $stmt = $this->prepareOrFail(
            'SELECT ' . self::TRANSLATION_SELECT . "
             {$baseFrom}
             {$where}
             ORDER BY l.`id` ASC
             LIMIT :limit OFFSET :offset",
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
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
                'per_page' => $perPage,
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
    //  Private — currency list WHERE builder
    // ================================================================== //

    /**
     * @param  array<string, int|string> $columnFilters
     * @return array{0: string, 1: list<int|string>}
     */
    private function buildWhereClause(?string $globalSearch, array $columnFilters): array
    {
        $conditions = [];
        /** @var list<int|string> $params */
        $params = [];

        if ($globalSearch !== null && $globalSearch !== '') {
            $like         = '%' . $this->escapeLike($globalSearch) . '%';
            $conditions[] = '(c.`code` LIKE ? OR c.`name` LIKE ? OR c.`symbol` LIKE ?)';
            $params[]     = $like;
            $params[]     = $like;
            $params[]     = $like;
        }

        foreach ($columnFilters as $column => $value) {
            if (!in_array($column, self::ALLOWED_FILTERS, true)) {
                continue;
            }
            $conditions[] = "c.`{$column}` = ?";
            $params[]     = $value;
        }

        $where = $conditions !== []
            ? 'WHERE ' . implode(' AND ', $conditions)
            : '';

        return [$where, $params];
    }

    // ================================================================== //
    //  Private — translation listing WHERE builder
    // ================================================================== //

    /**
     * Builds the WHERE clause for listTranslationsForCurrencyPaginated.
     * Uses named params (:key) so they can be merged safely with the
     * pagination named params (:limit, :offset).
     *
     * @param  array<string, int|string>  $columnFilters
     * @return array{0: string, 1: array<string, int|string>}
     *   [whereSql, namedParams]
     */
    private function buildTranslationWhereClause(
        int     $currencyId,
        ?string $globalSearch,
        array   $columnFilters,
    ): array {
        $conditions = ['l.`is_active` = 1'];

        /** @var array<string, int|string> $params */
        $params = [':currency_id' => $currencyId];

        // Global search — language name, code, or translated name
        if ($globalSearch !== null && trim($globalSearch) !== '') {
            $like                  = '%' . $this->escapeLike(trim($globalSearch)) . '%';
            $conditions[]          = '(l.`name` LIKE :global_text OR l.`code` LIKE :global_text OR ct.`name` LIKE :global_text)';
            $params[':global_text'] = $like;
        }

        // Column filters
        if (isset($columnFilters['language_id'])) {
            $conditions[]          = 'l.`id` = :language_id';
            $params[':language_id'] = (int) $columnFilters['language_id'];
        }

        if (isset($columnFilters['language_code'])) {
            $conditions[]             = 'l.`code` LIKE :language_code';
            $params[':language_code']  = '%' . $this->escapeLike((string) $columnFilters['language_code']) . '%';
        }

        if (isset($columnFilters['language_name'])) {
            $conditions[]             = 'l.`name` LIKE :language_name';
            $params[':language_name']  = '%' . $this->escapeLike((string) $columnFilters['language_name']) . '%';
        }

        if (isset($columnFilters['name'])) {
            $conditions[]    = 'ct.`name` LIKE :trans_name';
            $params[':trans_name'] = '%' . $this->escapeLike((string) $columnFilters['name']) . '%';
        }

        // has_translation filter: '1' = only translated, '0' = only untranslated
        // Uses ct.id IS NOT NULL — checks row existence, not name content.
        // Consistent with hasTranslation() in CurrencyTranslationDTO.
        if (isset($columnFilters['has_translation'])) {
            $val = (string) $columnFilters['has_translation'];
            if ($val === '1') {
                $conditions[] = 'ct.`id` IS NOT NULL';
            } elseif ($val === '0') {
                $conditions[] = 'ct.`id` IS NULL';
            }
        }

        $where = 'WHERE ' . implode(' AND ', $conditions);

        return [$where, $params];
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
