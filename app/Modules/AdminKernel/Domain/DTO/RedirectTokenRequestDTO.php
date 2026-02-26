<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO;

readonly class RedirectTokenRequestDTO
{
    public function __construct(
        public string $path
    ) {
    }
}
