<?php

declare(strict_types=1);

namespace Maatify\GeoSlim\Admin\Http\Routes;

use Maatify\AdminKernel\Http\Middleware\AuthorizationGuardMiddleware;
use Maatify\GeoSlim\Admin\Http\Controllers\Ui\CitiesListUiController;
use Maatify\GeoSlim\Admin\Http\Controllers\Ui\CityTranslationsListUiController;
use Maatify\GeoSlim\Admin\Http\Controllers\Ui\CountriesListUiController;
use Maatify\GeoSlim\Admin\Http\Controllers\Ui\CountryTranslationsListUiController;
use Psr\Container\ContainerInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;

final class GeoUiRoutes
{
    /**
     * @param RouteCollectorProxyInterface<ContainerInterface> $group
     */
    public static function register(RouteCollectorProxyInterface $group): void
    {
        $group->group('/geo', function (RouteCollectorProxyInterface $geo) {

            // Countries
            $geo->group('/countries', function (RouteCollectorProxyInterface $countries) {

                $countries->get('', [CountriesListUiController::class, '__invoke'])
                    ->setName('geo.countries.list.ui');

                $countries->get(
                    '/{country_id:[0-9]+}/translations',
                    [CountryTranslationsListUiController::class, '__invoke']
                )->setName('geo.countries.translations.list.ui');

                $countries->get(
                    '/{country_id:[0-9]+}/cities',
                    [CitiesListUiController::class, '__invoke']
                )->setName('geo.cities.list.ui');

                $countries->get(
                    '/{country_id:[0-9]+}/cities/{city_id:[0-9]+}/translations',
                    [CityTranslationsListUiController::class, '__invoke']
                )->setName('geo.cities.translations.list.ui');
            });

        })->add(AuthorizationGuardMiddleware::class);
    }
}

