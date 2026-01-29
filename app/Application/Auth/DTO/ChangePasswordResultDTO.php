<?php

declare(strict_types=1);

namespace App\Application\Auth\DTO;

final readonly class ChangePasswordResultDTO
{
    public function __construct(
        public bool $success,
    ) {
    }
}
