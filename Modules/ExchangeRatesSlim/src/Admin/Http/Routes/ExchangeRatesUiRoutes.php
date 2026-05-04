<?php

declare(strict_types=1);

namespace Maatify\ExchangeRatesSlim\Admin\Http\Routes;

use Maatify\AdminKernel\Http\Middleware\AuthorizationGuardMiddleware;
use Maatify\ExchangeRatesSlim\Admin\Http\Controllers\Ui\ProvidersListUiController;
use Maatify\ExchangeRatesSlim\Admin\Http\Controllers\Ui\RatesHistoryListUiController;
use Maatify\ExchangeRatesSlim\Admin\Http\Controllers\Ui\RatesListUiController;
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

            $erGroup->get('/rates/{rate_id:[0-9]+}/history', [RatesHistoryListUiController::class, '__invoke'])
                ->setName('exchange_rates.rates.history.list.ui');

        })->add(AuthorizationGuardMiddleware::class);
    }
}
