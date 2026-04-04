<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Routes\Ui\Features;

use Maatify\AdminKernel\Http\Controllers\Ui\Currency\CurrenciesListUiController;
use Maatify\AdminKernel\Http\Controllers\Ui\Currency\CurrencyTranslationsListUiController;
use Maatify\AdminKernel\Http\Middleware\AuthorizationGuardMiddleware;
use Psr\Container\ContainerInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;

final class CurrenciesUiRoutes
{
    /**
     * @param RouteCollectorProxyInterface<ContainerInterface> $group
     */
    public static function register(RouteCollectorProxyInterface $group): void
    {
        $group->group('/currencies', function (RouteCollectorProxyInterface $currenciesGroup) {

            $currenciesGroup->get('', [CurrenciesListUiController::class, '__invoke'])
                ->setName('currencies.list.ui');

            $currenciesGroup->get(
                '/{currency_id:[0-9]+}/translations',
                [CurrencyTranslationsListUiController::class, '__invoke']
            )
                ->setName('currencies.translations.list.ui');

        })->add(AuthorizationGuardMiddleware::class);
    }
}
