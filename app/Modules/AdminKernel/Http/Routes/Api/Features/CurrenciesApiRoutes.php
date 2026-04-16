<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Routes\Api\Features;

use Maatify\AdminKernel\Http\Controllers\Api\Currency\CurrenciesCreateController;
use Maatify\AdminKernel\Http\Controllers\Api\Currency\CurrenciesDropdownController;
use Maatify\AdminKernel\Http\Controllers\Api\Currency\CurrenciesQueryController;
use Maatify\AdminKernel\Http\Controllers\Api\Currency\CurrenciesSetActiveController;
use Maatify\AdminKernel\Http\Controllers\Api\Currency\CurrenciesUpdateController;
use Maatify\AdminKernel\Http\Controllers\Api\Currency\CurrenciesUpdateSortOrderController;
use Maatify\AdminKernel\Http\Controllers\Api\Currency\Translations\CurrencyTranslationDeleteController;
use Maatify\AdminKernel\Http\Controllers\Api\Currency\Translations\CurrencyTranslationsQueryController;
use Maatify\AdminKernel\Http\Controllers\Api\Currency\Translations\CurrencyTranslationUpsertController;
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
