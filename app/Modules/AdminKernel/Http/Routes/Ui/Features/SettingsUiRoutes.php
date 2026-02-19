<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Routes\Ui\Features;

use Psr\Container\ContainerInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;

final class SettingsUiRoutes
{
    /**
     * @param RouteCollectorProxyInterface<ContainerInterface> $group
     */
    public static function register(RouteCollectorProxyInterface $group): void
    {
        $group->get('/settings', [\Maatify\AdminKernel\Http\Controllers\Ui\UiSettingsController::class, 'index']);

        // UI sandbox for Twig/layout experimentation (non-canonical page)
        $group->get('/examples', [\Maatify\AdminKernel\Http\Controllers\Ui\UiExamplesController::class, 'index']);
    }
}
