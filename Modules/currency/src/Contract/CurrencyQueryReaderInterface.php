<?php

declare(strict_types=1);

namespace Maatify\Currency\Contract;

use Maatify\Currency\DTO\CurrencyDTO;
use Maatify\Currency\DTO\CurrencyTranslationDTO;

/**
 * Read side — all queries on currencies + currency_translations.
 *
 * ── Language / COALESCE behaviour ───────────────────────────────────────
 *
 *  $languageId = null
 *      No JOIN. CurrencyDTO::$translatedName is null.
 *      CurrencyDTO::$languageId is null.
 *
 *  $languageId = int
 *      LEFT JOIN currency_translations ON (currency_id, language_id).
 *      SELECT uses COALESCE(ct.name, c.name) → translatedName is ALWAYS
 *      a non-null string. Caller never needs to null-check display values.
 *      If you need to know whether a real translation row exists, call
 *      findTranslation() explicitly.
 */
interface CurrencyQueryReaderInterface
{
    // ================================================================== //
    //  Admin list — paginated, searchable, filterable
    // ================================================================== //

    /**
     * @param  array<string, int|string> $columnFilters  Allowed: is_active, code
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
    ): array;

    // ================================================================== //
    //  Website list — active only, no pagination
    // ================================================================== //

    /** @return list<CurrencyDTO> */
    public function listActiveCurrencies(?int $languageId = null): array;

    // ================================================================== //
    //  Single-record lookups
    // ================================================================== //

    public function findById(int $id, ?int $languageId = null): ?CurrencyDTO;

    public function findByCode(string $code, ?int $languageId = null): ?CurrencyDTO;

    // ================================================================== //
    //  Translation management (admin)
    // ================================================================== //

    /**
     * Returns the raw translation row for (currency_id, language_id),
     * or null if none exists yet.
     * Use this when you need to distinguish a real translation from a fallback.
     */
    public function findTranslation(int $currencyId, int $languageId): ?CurrencyTranslationDTO;

    /**
     * All translation rows for a given currency, ordered by language_id.
     *
     * @return list<CurrencyTranslationDTO>
     */
    public function listTranslationsForCurrency(int $currencyId): array;

    // ================================================================== //
    //  Aggregates needed by the write side
    // ================================================================== //

    public function maxDisplayOrder(): int;
}
