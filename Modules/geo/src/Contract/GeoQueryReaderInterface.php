<?php

declare(strict_types=1);

namespace Maatify\Geo\Contract;

/**
 * @deprecated Replaced by entity-specific interfaces.
 *             Use CountryRepositoryInterface, CountryDropdownRepositoryInterface,
 *             CountryTranslationRepositoryInterface, CityRepositoryInterface,
 *             CityDropdownRepositoryInterface, or CityTranslationRepositoryInterface instead.
 */
interface GeoQueryReaderInterface extends
    CountryRepositoryInterface,
    CountryDropdownRepositoryInterface,
    CountryTranslationRepositoryInterface,
    CityRepositoryInterface,
    CityDropdownRepositoryInterface,
    CityTranslationRepositoryInterface
{
}
