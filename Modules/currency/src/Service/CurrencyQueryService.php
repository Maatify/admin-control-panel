<?php

declare(strict_types=1);

namespace Maatify\Currency\Service;

use Maatify\Currency\Contract\CurrencyQueryReaderInterface;
use Maatify\Currency\DTO\CurrencyDTO;
use Maatify\Currency\DTO\CurrencyTranslationDTO;
use Maatify\Currency\Exception\CurrencyNotFoundException;

/**
 * Read-side service.
 *
 * All public methods accept an optional $languageId:
 *   null → base name only, translatedName is null in the DTO.
 *   int  → COALESCE join: translatedName is always a non-null string.
 */
final class CurrencyQueryService
{
    public function __construct(
        private readonly CurrencyQueryReaderInterface $reader,
    ) {}

    // ------------------------------------------------------------------ //
    //  Admin
    // ------------------------------------------------------------------ //

    /**
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
    public function paginate(
        int     $page          = 1,
        int     $perPage       = 20,
        ?string $globalSearch  = null,
        array   $columnFilters = [],
        ?int    $languageId    = null,
    ): array {
        return $this->reader->listCurrencies(
            $page,
            $perPage,
            $globalSearch,
            $columnFilters,
            $languageId,
        );
    }

    // ------------------------------------------------------------------ //
    //  Website
    // ------------------------------------------------------------------ //

    /**
     * @return list<CurrencyDTO>
     */
    public function activeList(?int $languageId = null): array
    {
        return $this->reader->listActiveCurrencies($languageId);
    }

    // ------------------------------------------------------------------ //
    //  Single-record
    // ------------------------------------------------------------------ //

    /**
     * @throws CurrencyNotFoundException
     */
    public function getById(int $id, ?int $languageId = null): CurrencyDTO
    {
        $dto = $this->reader->findById($id, $languageId);
        if ($dto === null) {
            throw CurrencyNotFoundException::withId($id);
        }

        return $dto;
    }

    /**
     * @throws CurrencyNotFoundException
     */
    public function getByCode(string $code, ?int $languageId = null): CurrencyDTO
    {
        $dto = $this->reader->findByCode($code, $languageId);
        if ($dto === null) {
            throw CurrencyNotFoundException::withCode($code);
        }

        return $dto;
    }

    // ------------------------------------------------------------------ //
    //  Translation management (admin)
    // ------------------------------------------------------------------ //

    /**
     * Returns the raw translation row, or null if none exists yet.
     * Use this to distinguish a real translation from a COALESCE fallback.
     */
    public function findTranslation(int $currencyId, int $languageId): ?CurrencyTranslationDTO
    {
        return $this->reader->findTranslation($currencyId, $languageId);
    }

    /**
     * Paginated, searchable, filterable translation list for admin screens.
     * Returns all active languages — including those without a translation row.
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
    public function listTranslationsPaginated(
        int     $currencyId,
        int     $page          = 1,
        int     $perPage       = 20,
        ?string $globalSearch  = null,
        array   $columnFilters = [],
    ): array {
        return $this->reader->listTranslationsForCurrencyPaginated(
            $currencyId,
            $page,
            $perPage,
            $globalSearch,
            $columnFilters,
        );
    }
}
