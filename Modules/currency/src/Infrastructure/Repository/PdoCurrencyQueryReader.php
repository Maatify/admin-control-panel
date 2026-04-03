<?php

declare(strict_types=1);

namespace Maatify\Currency\Infrastructure\Repository;

use Maatify\Currency\Contract\CurrencyQueryReaderInterface;
use Maatify\Currency\DTO\CurrencyDTO;
use Maatify\Currency\DTO\CurrencyTranslationDTO;
use PDO;
use PDOStatement;
use RuntimeException;

final class PdoCurrencyQueryReader implements CurrencyQueryReaderInterface
{
    /** Columns that callers are allowed to filter on directly. */
    private const ALLOWED_FILTERS = ['is_active', 'code'];

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

        // Total (no filters, no join needed)
        $total = $this->scalarInt('SELECT COUNT(*) FROM `currencies`');

        // Filtered count (search/column filters only — join not needed for counting)
        $filtered = $this->scalarInt(
            "SELECT COUNT(*) FROM `currencies` AS c {$where}",
            $filterParams,
        );

        // Data page (join here to get localised display names)
        // NOTE: Use only positional ? throughout — PDO forbids mixing ? and :name
        //       in the same statement (SQLSTATE[HY093]).
        $sql = "
            SELECT c.*, {$selectExtra}
            FROM   `currencies` AS c
            {$join}
            {$where}
            ORDER BY c.`display_order` ASC, c.`id` ASC
            LIMIT ? OFFSET ?
        ";

        $stmt = $this->prepareOrFail($sql);

        // Bind order must match ? positions in SQL: join → filter → pagination
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
    //  Translation management
    // ================================================================== //

    /** {@inheritDoc} */
    public function findTranslation(int $currencyId, int $languageId): ?CurrencyTranslationDTO
    {
        $stmt = $this->prepareOrFail(
            'SELECT * FROM `currency_translations`
             WHERE `currency_id` = ? AND `language_id` = ?
             LIMIT 1',
        );
        $stmt->execute([$currencyId, $languageId]);

        $row = $this->fetchAssoc($stmt);

        return $row !== null ? CurrencyTranslationDTO::fromRow($row) : null;
    }

    /**
     * {@inheritDoc}
     *
     * @return list<CurrencyTranslationDTO>
     */
    public function listTranslationsForCurrency(int $currencyId): array
    {
        $stmt = $this->prepareOrFail(
            'SELECT * FROM `currency_translations`
             WHERE  `currency_id` = ?
             ORDER BY `language_id` ASC',
        );
        $stmt->execute([$currencyId]);

        /** @var list<CurrencyTranslationDTO> $result */
        $result = array_map(
            static fn (array $row): CurrencyTranslationDTO => CurrencyTranslationDTO::fromRow($row),
            $this->fetchAllAssoc($stmt),
        );

        return $result;
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
    //  Private — translation JOIN builder
    // ================================================================== //

    /**
     * Builds the SELECT fragment + LEFT JOIN for optional translation lookup.
     *
     * When $languageId is null:
     *   • No JOIN is added.
     *   • translated_name and translation_language_id are both NULL in the result.
     *
     * When $languageId is an int:
     *   • LEFT JOIN currency_translations ON (currency_id = c.id AND language_id = ?).
     *   • COALESCE(ct.name, c.name) → translated_name is NEVER null.
     *   • translation_language_id carries the requested language_id (even when
     *     no translation row existed and the COALESCE fallback fired).
     *
     * Param positions in the returned SQL fragments (both ? are positional):
     *   [0] in SELECT  : ? AS `translation_language_id`
     *   [1] in JOIN ON : ct.`language_id` = ?
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
            // COALESCE: real translation → base name fallback (never null)
            'COALESCE(ct.`name`, c.`name`) AS `translated_name`, ? AS `translation_language_id`',
            'LEFT JOIN `currency_translations` ct
                    ON ct.`currency_id` = c.`id` AND ct.`language_id` = ?',
            [$languageId, $languageId], // pos[0]: SELECT alias, pos[1]: JOIN ON
        ];
    }

    // ================================================================== //
    //  Private — WHERE builder
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
                continue; // silently ignore unknown columns — no SQL injection risk
            }
            $conditions[] = "c.`{$column}` = ?";
            $params[]     = $value;
        }

        $where = $conditions !== []
            ? 'WHERE ' . implode(' AND ', $conditions)
            : '';

        return [$where, $params];
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    // ================================================================== //
    //  Private — PDO helpers
    // ================================================================== //

    private function prepareOrFail(string $sql): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        if ($stmt === false) {
            throw new RuntimeException("PDO::prepare failed for: {$sql}");
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
        // PDOStatement::fetchAll() is typed as array (not array|false) in PHPStan
        // stubs when PDO::ERRMODE_EXCEPTION is active — the false branch is unreachable.
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
