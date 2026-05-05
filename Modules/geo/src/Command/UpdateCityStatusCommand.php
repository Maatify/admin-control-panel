<?php

declare(strict_types=1);

namespace Maatify\Geo\Command;

use Maatify\Geo\Exception\GeoInvalidArgumentException;

/** Toggles the active / inactive flag for a city. */
final readonly class UpdateCityStatusCommand
{
    public function __construct(
        public int  $id,
        public bool $isActive,
    ) {
        if ($id < 1) {
            throw GeoInvalidArgumentException::invalidId('id');
        }
    }
}
