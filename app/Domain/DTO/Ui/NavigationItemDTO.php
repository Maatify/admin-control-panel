<?php

declare(strict_types=1);

namespace App\Domain\DTO\Ui;

readonly class NavigationItemDTO
{
    public function __construct(
        public string $title,
        public string $path,
        public string $icon
    ) {
    }
}
