<?php

declare(strict_types=1);

namespace Maatify\GeoSlim\Admin\Http\Routes;

use Maatify\GeoSlim\Admin\Http\Controllers\Api\Cities\CitiesCreateController;
use Maatify\GeoSlim\Admin\Http\Controllers\Api\Cities\CitiesDropdownController;
use Maatify\GeoSlim\Admin\Http\Controllers\Api\Cities\CitiesQueryController;
use Maatify\GeoSlim\Admin\Http\Controllers\Api\Cities\CitiesSetActiveController;
use Maatify\GeoSlim\Admin\Http\Controllers\Api\Cities\CitiesUpdateController;
use Maatify\GeoSlim\Admin\Http\Controllers\Api\Cities\CitiesUpdateSortOrderController;
use Maatify\GeoSlim\Admin\Http\Controllers\Api\Cities\Translations\CityTranslationDeleteController;
use Maatify\GeoSlim\Admin\Http\Controllers\Api\Cities\Translations\CityTranslationUpsertController;
use Maatify\GeoSlim\Admin\Http\Controllers\Api\Cities\Translations\CityTranslationsQueryController;
use Maatify\GeoSlim\Admin\Http\Controllers\Api\Countries\CountriesCreateController;
use Maatify\GeoSlim\Admin\Http\Controllers\Api\Countries\CountriesDropdownController;
use Maatify\GeoSlim\Admin\Http\Controllers\Api\Countries\CountriesQueryController;
use Maatify\GeoSlim\Admin\Http\Controllers\Api\Countries\CountriesSetActiveController;
use Maatify\GeoSlim\Admin\Http\Controllers\Api\Countries\CountriesUpdateController;
use Maatify\GeoSlim\Admin\Http\Controllers\Api\Countries\CountriesUpdateSortOrderController;
use Maatify\GeoSlim\Admin\Http\Controllers\Api\Countries\Translations\CountryTranslationDeleteController;
use Maatify\GeoSlim\Admin\Http\Controllers\Api\Countries\Translations\CountryTranslationUpsertController;
use Maatify\GeoSlim\Admin\Http\Controllers\Api\Countries\Translations\CountryTranslationsQueryController;
use Psr\Container\ContainerInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;

class GeoApiRoutes
{
    /**
     * @param RouteCollectorProxyInterface<ContainerInterface> $group
     */
    public static function register(RouteCollectorProxyInterface $group): void
    {
        $group->group('/geo', function (RouteCollectorProxyInterface $geo) {

            // ─────────────────────────────
            // Countries
            // ─────────────────────────────
            $geo->group('/countries', function (RouteCollectorProxyInterface $countries) {

                $countries->post('/dropdown', [CountriesDropdownController::class, '__invoke'])
                    ->setName('geo.countries.dropdown.api');

                $countries->post('/query', [CountriesQueryController::class, '__invoke'])
                    ->setName('geo.countries.list.api');

                $countries->post('/create', [CountriesCreateController::class, '__invoke'])
                    ->setName('geo.countries.create.api');

                $countries->post('/update', [CountriesUpdateController::class, '__invoke'])
                    ->setName('geo.countries.update.api');

                $countries->post('/set-active', [CountriesSetActiveController::class, '__invoke'])
                    ->setName('geo.countries.set_active.api');

                $countries->post('/update-sort', [CountriesUpdateSortOrderController::class, '__invoke'])
                    ->setName('geo.countries.update_sort.api');

                // ─────────────────────────────
                // Country Translations
                // ─────────────────────────────
                $countries->group('/{country_id:[0-9]+}/translations', function (RouteCollectorProxyInterface $countryTranslations) {

                    $countryTranslations->post('/query', [CountryTranslationsQueryController::class, '__invoke'])
                        ->setName('geo.countries.translations.list.api');

                    $countryTranslations->post('/upsert', [CountryTranslationUpsertController::class, '__invoke'])
                        ->setName('geo.countries.translations.upsert.api');

                    $countryTranslations->post('/delete', [CountryTranslationDeleteController::class, '__invoke'])
                        ->setName('geo.countries.translations.delete.api');
                });
            });

            // ─────────────────────────────
            // Cities
            // ─────────────────────────────
            $geo->group('/cities', function (RouteCollectorProxyInterface $cities) {

                $cities->post('/dropdown', [CitiesDropdownController::class, '__invoke'])
                    ->setName('geo.cities.dropdown.api');

                $cities->post('/query', [CitiesQueryController::class, '__invoke'])
                    ->setName('geo.cities.list.api');

                $cities->post('/create', [CitiesCreateController::class, '__invoke'])
                    ->setName('geo.cities.create.api');

                $cities->post('/update', [CitiesUpdateController::class, '__invoke'])
                    ->setName('geo.cities.update.api');

                $cities->post('/set-active', [CitiesSetActiveController::class, '__invoke'])
                    ->setName('geo.cities.set_active.api');

                $cities->post('/update-sort', [CitiesUpdateSortOrderController::class, '__invoke'])
                    ->setName('geo.cities.update_sort.api');

                // ─────────────────────────────
                // City Translations
                // ─────────────────────────────
                $cities->group('/{city_id:[0-9]+}/translations', function (RouteCollectorProxyInterface $cityTranslations) {

                    $cityTranslations->post('/query', [CityTranslationsQueryController::class, '__invoke'])
                        ->setName('geo.cities.translations.list.api');

                    $cityTranslations->post('/upsert', [CityTranslationUpsertController::class, '__invoke'])
                        ->setName('geo.cities.translations.upsert.api');

                    $cityTranslations->post('/delete', [CityTranslationDeleteController::class, '__invoke'])
                        ->setName('geo.cities.translations.delete.api');
                });
            });
        });
    }
}

