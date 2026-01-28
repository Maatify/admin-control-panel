<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Ui;

use App\Domain\DTO\Ui\NavigationItemDTO;

interface NavigationProviderInterface
{
    /**
     * @return NavigationItemDTO[]
     */
    public function getNavigationItems(): array;
}
