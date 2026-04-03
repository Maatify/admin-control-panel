<?php

declare(strict_types=1);

namespace Maatify\Currency\Contract;

use Maatify\Currency\DTO\CurrencyDTO;
use Maatify\Currency\DTO\CurrencyTranslationDTO;

/**
 * Read side — all queries on currencies + currency_translations.
 *
 * ── Language / COALESCE behaviour (list + lookup) ───────────────────────
 *
 *  $languageId = null
 *      No JOIN. CurrencyDTO::$translatedName is null.
 *
 *  $languageId = int
 *      LEFT JOIN currency_translations ON (currency_id, language_id).
 *      COALESCE(ct.name, c.name) → translatedName is ALWAYS a non-null string.
 *      Call findTranslation() if you need to know whether a real row exists.
 *
 * ── Translation listing (admin) ──────────────────────────────────────────
 *
 *  listTranslationsForCurrency() and its paginated variant LEFT JOIN the
 *  `languages` table so every active language is represented, including
 *  those without a translation row. The DTO's $translatedName is null for
 *  untranslated languages — callers use this to render "Add translation".
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
     * Returns the translation row enriched with language identity, or null
     * if no translation exists for the given (currency_id, language_id) pair.
     *
     * Uses INNER JOIN — returned DTO has no null translation fields.
     * Use this to distinguish a real translation from a COALESCE fallback.
     */
    public function findTranslation(int $currencyId, int $languageId): ?CurrencyTranslationDTO;

    /**
     * Returns ALL active languages LEFT JOINed with the translation table.
     * Languages without a translation row have $dto->translatedName === null.
     *
     * @return list<CurrencyTranslationDTO>
     */
    public function listTranslationsForCurrency(int $currencyId): array;

    /**
     * Paginated, searchable, filterable version of listTranslationsForCurrency().
     *
     * columnFilters supported keys:
     *   language_id   (int)    — exact match
     *   language_code (string) — LIKE
     *   language_name (string) — LIKE
     *   name          (string) — LIKE on translated name
     *   has_translation ('0'|'1') — filter by whether translation exists
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
    ): array;

    // ================================================================== //
    //  Aggregates needed by the write side
    // ================================================================== //

    public function maxDisplayOrder(): int;
}
