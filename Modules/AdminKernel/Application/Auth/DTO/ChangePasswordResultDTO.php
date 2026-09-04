<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Application\Auth\DTO;

final readonly class ChangePasswordResultDTO
{
    public function __construct(
        public bool $success,
    ) {
    }
}
