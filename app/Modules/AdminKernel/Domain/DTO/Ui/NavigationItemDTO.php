<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO\Ui;

readonly class NavigationItemDTO
{
    public function __construct(
        public string $title,
        public string $path,
        public string $icon
    ) {
    }
}
