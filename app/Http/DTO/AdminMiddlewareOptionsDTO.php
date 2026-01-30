<?php

declare(strict_types=1);

namespace App\Http\DTO;

final class AdminMiddlewareOptionsDTO
{
    public function __construct(
        public bool $withInfrastructure = true
    ) {
    }
}
