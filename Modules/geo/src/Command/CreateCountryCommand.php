<?php

declare(strict_types=1);

namespace Maatify\Geo\Command;

use Maatify\Geo\Exception\GeoInvalidArgumentException;

/**
 * Carries all data required to persist a new country.
 *
 * display_order is auto-assigned by the repository via ScopedOrderingManager.
 * icon is updated independently via a dedicated method.
 */
final readonly class CreateCountryCommand
{
    public function __construct(
        public string  $code,
        public string  $name,
        public ?string $icon     = null,
        public bool    $isActive = true,
    ) {
        $trimmedCode = trim($code);
        if ($trimmedCode === '') {
            throw GeoInvalidArgumentException::emptyField('code');
        }
        if (strlen($trimmedCode) !== 2) {
            throw GeoInvalidArgumentException::invalidCode($code);
        }
        if (trim($name) === '') {
            throw GeoInvalidArgumentException::emptyField('name');
        }
    }
}
