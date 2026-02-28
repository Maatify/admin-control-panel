<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Routes\Ui\Features;

use Psr\Container\ContainerInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;

final class TwoFactorUiRoutes
{
    /**
     * @param RouteCollectorProxyInterface<ContainerInterface> $group
     */
    public static function register(RouteCollectorProxyInterface $group): void
    {
        $group->get(
            '/2fa/setup',
            [\Maatify\AdminKernel\Http\Controllers\Ui\Auth\UiTwoFactorSetupController::class, 'index']
        )
            ->setName('2fa.setup');

        $group->post(
            '/2fa/setup',
            [\Maatify\AdminKernel\Http\Controllers\Ui\Auth\UiTwoFactorSetupController::class, 'enable']
        )
            ->setName('2fa.enable');
    }
}
