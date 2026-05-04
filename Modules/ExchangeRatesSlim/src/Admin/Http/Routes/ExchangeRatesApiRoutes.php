<?php

declare(strict_types=1);

namespace Maatify\ExchangeRatesSlim\Admin\Http\Routes;

use Maatify\ExchangeRatesSlim\Admin\Http\Controllers\Api\Providers\ProvidersCreateController;
use Maatify\ExchangeRatesSlim\Admin\Http\Controllers\Api\Providers\ProvidersDeleteController;
use Maatify\ExchangeRatesSlim\Admin\Http\Controllers\Api\Providers\ProvidersDropdownController;
use Maatify\ExchangeRatesSlim\Admin\Http\Controllers\Api\Providers\ProvidersQueryController;
use Maatify\ExchangeRatesSlim\Admin\Http\Controllers\Api\Providers\ProvidersSetActiveController;
use Maatify\ExchangeRatesSlim\Admin\Http\Controllers\Api\Providers\ProvidersUpdateController;
use Maatify\ExchangeRatesSlim\Admin\Http\Controllers\Api\Providers\ProvidersUpdateSortOrderController;
use Maatify\ExchangeRatesSlim\Admin\Http\Controllers\Api\Rates\RateHistoryQueryController;
use Maatify\ExchangeRatesSlim\Admin\Http\Controllers\Api\Rates\RatesCreateController;
use Maatify\ExchangeRatesSlim\Admin\Http\Controllers\Api\Rates\RatesDeleteController;
use Maatify\ExchangeRatesSlim\Admin\Http\Controllers\Api\Rates\RatesQueryController;
use Maatify\ExchangeRatesSlim\Admin\Http\Controllers\Api\Rates\RatesSetActiveController;
use Maatify\ExchangeRatesSlim\Admin\Http\Controllers\Api\Rates\RatesUpdateController;
use Maatify\ExchangeRatesSlim\Admin\Http\Controllers\Api\Rates\RatesUpdateSortOrderController;
use Psr\Container\ContainerInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;

class ExchangeRatesApiRoutes
{
    /**
     * @param RouteCollectorProxyInterface<ContainerInterface> $group
     */
    public static function register(RouteCollectorProxyInterface $group): void
    {
        $group->group('/exchange-rates', function (RouteCollectorProxyInterface $erGroup) {

            // ─────────────────────────────
            // Providers Control
            // ─────────────────────────────
            $erGroup->group('/providers', function (RouteCollectorProxyInterface $providers) {
                $providers->post('/dropdown', [ProvidersDropdownController::class, '__invoke'])
                    ->setName('exchange_rates.providers.dropdown.api');

                $providers->post('/query', [ProvidersQueryController::class, '__invoke'])
                    ->setName('exchange_rates.providers.list.api');

                $providers->post('/create', [ProvidersCreateController::class, '__invoke'])
                    ->setName('exchange_rates.providers.create.api');

                $providers->post('/update', [ProvidersUpdateController::class, '__invoke'])
                    ->setName('exchange_rates.providers.update.api');

                $providers->post('/set-active', [ProvidersSetActiveController::class, '__invoke'])
                    ->setName('exchange_rates.providers.set_active.api');

                $providers->post('/update-sort', [ProvidersUpdateSortOrderController::class, '__invoke'])
                    ->setName('exchange_rates.providers.update_sort.api');

                $providers->post('/delete', [ProvidersDeleteController::class, '__invoke'])
                    ->setName('exchange_rates.providers.delete.api');
            });

            // ─────────────────────────────
            // Rates Control
            // ─────────────────────────────
            $erGroup->group('/rates', function (RouteCollectorProxyInterface $rates) {
                $rates->post('/query', [RatesQueryController::class, '__invoke'])
                    ->setName('exchange_rates.rates.list.api');

                $rates->post('/create', [RatesCreateController::class, '__invoke'])
                    ->setName('exchange_rates.rates.create.api');

                $rates->post('/update', [RatesUpdateController::class, '__invoke'])
                    ->setName('exchange_rates.rates.update.api');

                $rates->post('/set-active', [RatesSetActiveController::class, '__invoke'])
                    ->setName('exchange_rates.rates.set_active.api');

                $rates->post('/update-sort', [RatesUpdateSortOrderController::class, '__invoke'])
                    ->setName('exchange_rates.rates.update_sort.api');

                $rates->post('/delete', [RatesDeleteController::class, '__invoke'])
                    ->setName('exchange_rates.rates.delete.api');

                $rates->post('/history/query', [RateHistoryQueryController::class, '__invoke'])
                    ->setName('exchange_rates.rates.history.api');
            });

        });
    }
}
