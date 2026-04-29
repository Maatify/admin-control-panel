<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Routes\Ui\Features;

use Maatify\AdminKernel\Http\Controllers\Ui\ExchangeRates\ProvidersListUiController;
use Maatify\AdminKernel\Http\Controllers\Ui\ExchangeRates\RatesListUiController;
use Maatify\AdminKernel\Http\Middleware\AuthorizationGuardMiddleware;
use Psr\Container\ContainerInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;

final class ExchangeRatesUiRoutes
{
    /**
     * @param RouteCollectorProxyInterface<ContainerInterface> $group
     */
    public static function register(RouteCollectorProxyInterface $group): void
    {
        $group->group('/exchange-rates', function (RouteCollectorProxyInterface $erGroup) {

            $erGroup->get('/providers', [ProvidersListUiController::class, '__invoke'])
                ->setName('exchange_rates.providers.list.ui');

            $erGroup->get('/rates', [RatesListUiController::class, '__invoke'])
                ->setName('exchange_rates.rates.list.ui');

        })->add(AuthorizationGuardMiddleware::class);
    }
}
