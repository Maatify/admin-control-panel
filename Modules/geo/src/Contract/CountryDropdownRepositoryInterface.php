<?php

declare(strict_types=1);

namespace Maatify\Geo\Contract;

use Maatify\Geo\DTO\CountryDTO;

/**
 * Lightweight read-only interface for populating country dropdown lists.
 *
 * Returns only active countries — no pagination, no filters.
 * Kept separate so callers that only need a dropdown do not take
 * a dependency on the full CountryRepositoryInterface.
 */
interface CountryDropdownRepositoryInterface
{
    /**
     * All active countries ordered by display_order ASC.
     *
     * @return list<CountryDTO>
     */
    public function listActiveCountries(?int $languageId = null): array;
}

