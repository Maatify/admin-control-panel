<?php

declare(strict_types=1);

namespace Maatify\Geo\Contract;

use Maatify\Geo\DTO\CityDTO;

/**
 * Lightweight read-only interface for populating city dropdown lists.
 *
 * Returns only active cities — no pagination, no filters.
 * Provided separately so callers that only need a dropdown do not depend
 * on the full CityRepositoryInterface.
 */
interface CityDropdownRepositoryInterface
{
    /**
     * Active cities for a country identified by its numeric ID.
     *
     * @return list<CityDTO>
     */
    public function listActiveCitiesByCountryId(int $countryId, ?int $languageId = null): array;

    /**
     * Active cities for a country identified by its ISO alpha-2 code.
     *
     * @return list<CityDTO>
     */
    public function listActiveCitiesByCountryCode(string $countryCode, ?int $languageId = null): array;
}

