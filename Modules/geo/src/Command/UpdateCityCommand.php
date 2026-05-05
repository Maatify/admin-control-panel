<?php

declare(strict_types=1);

namespace Maatify\Geo\Command;

use Maatify\Geo\Exception\GeoInvalidArgumentException;

/**
 * Carries all data required to update an existing city.
 *
 * display_order is updated via a dedicated reorder method.
 */
final readonly class UpdateCityCommand
{
    public function __construct(
        public int     $id,
        public string  $name,
        public ?string $code,
        public bool    $isActive,
    ) {
        if ($id < 1) {
            throw GeoInvalidArgumentException::invalidId('id');
        }
        if (trim($name) === '') {
            throw GeoInvalidArgumentException::emptyField('name');
        }
        if ($code !== null && trim($code) === '') {
            throw GeoInvalidArgumentException::emptyField('code');
        }
    }
}
