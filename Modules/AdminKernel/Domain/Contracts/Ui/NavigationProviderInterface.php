<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Contracts\Ui;

use Maatify\AdminKernel\Domain\DTO\Ui\NavigationItemDTO;

interface NavigationProviderInterface
{
    /**
     * @return NavigationItemDTO[]
     */
    public function getNavigationItems(): array;
}
