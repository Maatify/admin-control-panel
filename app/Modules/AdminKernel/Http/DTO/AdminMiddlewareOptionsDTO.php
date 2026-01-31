<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\DTO;

final class AdminMiddlewareOptionsDTO
{
    public function __construct(
        public bool $withInfrastructure = true
    ) {
    }
}
