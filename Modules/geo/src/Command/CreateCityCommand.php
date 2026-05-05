<?php

declare(strict_types=1);

namespace Maatify\Geo\Command;

use Maatify\Geo\Exception\GeoInvalidArgumentException;

/**
 * Carries all data required to persist a new city.
 *
 * display_order is auto-assigned by the repository, scoped per country.
 */
final readonly class CreateCityCommand
{
    public function __construct(
        public int     $countryId,
        public string  $name,
        public ?string $code     = null,
        public bool    $isActive = true,
    ) {
        if ($countryId < 1) {
            throw GeoInvalidArgumentException::invalidId('countryId');
        }
        if (trim($name) === '') {
            throw GeoInvalidArgumentException::emptyField('name');
        }
        if ($code !== null && trim($code) === '') {
            throw GeoInvalidArgumentException::emptyField('code');
        }
    }
}
