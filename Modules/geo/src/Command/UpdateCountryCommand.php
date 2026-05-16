<?php

declare(strict_types=1);

namespace Maatify\Geo\Command;

use Maatify\Geo\Exception\GeoInvalidArgumentException;

/**
 * Carries all data required to update an existing country.
 *
 * display_order is updated via a dedicated reorder method.
 * icon is updated via a dedicated method.
 */
final readonly class UpdateCountryCommand
{
    public function __construct(
        public int     $id,
        public string  $code,
        public string  $name,
        public ?string $phoneCode,
        public ?string $currency,
        public ?string $icon,
        public bool    $isActive,
        public ?bool   $isStateRequired = null,
        public ?bool   $isPostcodeRequired = null,
    ) {
        if ($id < 1) {
            throw GeoInvalidArgumentException::invalidId('id');
        }
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
