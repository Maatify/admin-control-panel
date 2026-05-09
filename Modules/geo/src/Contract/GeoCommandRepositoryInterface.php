<?php

declare(strict_types=1);

namespace Maatify\Geo\Contract;

/**
 * @deprecated Replaced by entity-specific interfaces.
 *             Use CountryRepositoryInterface, CountryTranslationRepositoryInterface,
 *             CityRepositoryInterface, or CityTranslationRepositoryInterface instead.
 */
interface GeoCommandRepositoryInterface extends
    CountryRepositoryInterface,
    CountryTranslationRepositoryInterface,
    CityRepositoryInterface,
    CityTranslationRepositoryInterface
{
}
