<?php

declare(strict_types=1);

namespace Maatify\CurrencySlim\Admin\Http\Routes;

use Maatify\CurrencySlim\Admin\Http\Controllers\Api\CurrenciesCreateController;
use Maatify\CurrencySlim\Admin\Http\Controllers\Api\CurrenciesDropdownController;
use Maatify\CurrencySlim\Admin\Http\Controllers\Api\CurrenciesQueryController;
use Maatify\CurrencySlim\Admin\Http\Controllers\Api\CurrenciesSetActiveController;
use Maatify\CurrencySlim\Admin\Http\Controllers\Api\CurrenciesUpdateController;
use Maatify\CurrencySlim\Admin\Http\Controllers\Api\CurrenciesUpdateSortOrderController;
use Maatify\CurrencySlim\Admin\Http\Controllers\Api\Translations\CurrencyTranslationDeleteController;
use Maatify\CurrencySlim\Admin\Http\Controllers\Api\Translations\CurrencyTranslationsQueryController;
use Maatify\CurrencySlim\Admin\Http\Controllers\Api\Translations\CurrencyTranslationUpsertController;
use Psr\Container\ContainerInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;

class CurrenciesApiRoutes
{
    /**
     * @param RouteCollectorProxyInterface<ContainerInterface> $group
     */
    public static function register(RouteCollectorProxyInterface $group): void
    {
        // ─────────────────────────────
        // Currencies Control
        // ─────────────────────────────
        $group->group('/currencies', function (RouteCollectorProxyInterface $currencies) {

            /**
             * UI context selector (dropdown)
             */
            $currencies->post('/dropdown', [CurrenciesDropdownController::class, '__invoke'])
                ->setName('currencies.dropdown.api');

            $currencies->post('/query', [CurrenciesQueryController::class, '__invoke'])
                ->setName('currencies.list.api');

            $currencies->post('/create', [CurrenciesCreateController::class, '__invoke'])
                ->setName('currencies.create.api');

            $currencies->post('/update', [CurrenciesUpdateController::class, '__invoke'])
                ->setName('currencies.update.api');

            $currencies->post('/set-active', [CurrenciesSetActiveController::class, '__invoke'])
                ->setName('currencies.set_active.api');

            $currencies->post('/update-sort', [CurrenciesUpdateSortOrderController::class, '__invoke'])
                ->setName('currencies.update_sort.api');

            // ─────────────────────────────
            // Currency translations Control
            // ─────────────────────────────
            $currencies->group('/{currency_id:[0-9]+}/translations', function (RouteCollectorProxyInterface $currencyTranslations) {

                $currencyTranslations->post('/query', [CurrencyTranslationsQueryController::class, '__invoke'])
                    ->setName('currencies.translations.list.api');

                $currencyTranslations->post('/upsert', [CurrencyTranslationUpsertController::class, '__invoke'])
                    ->setName('currencies.translations.upsert.api');

                $currencyTranslations->post('/delete', [CurrencyTranslationDeleteController::class, '__invoke'])
                    ->setName('currencies.translations.delete.api');
            });

        });
    }
}
