<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Routes\Api\Features;

use Maatify\AdminKernel\Http\Controllers\Api\I18n\Languages\LanguagesClearFallbackController;
use Maatify\AdminKernel\Http\Controllers\Api\I18n\Languages\LanguagesCreateController;
use Maatify\AdminKernel\Http\Controllers\Api\I18n\Languages\LanguagesQueryController;
use Maatify\AdminKernel\Http\Controllers\Api\I18n\Languages\LanguagesSetActiveController;
use Maatify\AdminKernel\Http\Controllers\Api\I18n\Languages\LanguagesSetFallbackController;
use Maatify\AdminKernel\Http\Controllers\Api\I18n\Languages\LanguagesUpdateCodeController;
use Maatify\AdminKernel\Http\Controllers\Api\I18n\Languages\LanguagesUpdateNameController;
use Maatify\AdminKernel\Http\Controllers\Api\I18n\Languages\LanguagesUpdateSettingsController;
use Maatify\AdminKernel\Http\Controllers\Api\I18n\Languages\LanguagesUpdateSortOrderController;
use Maatify\LanguageCore\Http\Controllers\Api\LanguageDropdownController;
use Psr\Container\ContainerInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;

class LanguagesApiRoutes
{
    /**
     * @param RouteCollectorProxyInterface<ContainerInterface> $group
     */
    public static function register(RouteCollectorProxyInterface $group): void
    {
        // ─────────────────────────────
        // languages Control
        // ─────────────────────────────
        $group->group('/languages', function (RouteCollectorProxyInterface $languages) {

            /**
             * UI context selector (dropdown)
             * Permission: i18n.languages.dropdown.api
             * (mapped via PermissionMapperV2 anyOf)
             */
            $languages->post('/dropdown', LanguageDropdownController::class)
                ->setName('i18n.languages.dropdown.api');

            $languages->post('/query', [LanguagesQueryController::class, '__invoke'])
                ->setName('languages.list.api');

            $languages->post('/create', [LanguagesCreateController::class, '__invoke'])
                ->setName('languages.create.api');

            $languages->post('/update-settings', [LanguagesUpdateSettingsController::class, '__invoke'])
                ->setName('languages.update.settings.api');

            $languages->post('/set-active', [LanguagesSetActiveController::class, '__invoke'])
                ->setName('languages.set.active.api');

            $languages->post('/set-fallback', [LanguagesSetFallbackController::class, '__invoke'])
                ->setName('languages.set.fallback.api');

            $languages->post('/clear-fallback', [LanguagesClearFallbackController::class, '__invoke'])
                ->setName('languages.clear.fallback.api');

            $languages->post('/update-sort', [LanguagesUpdateSortOrderController::class, '__invoke'])
                ->setName('languages.update.sort.api');

            $languages->post('/update-name', [LanguagesUpdateNameController::class, '__invoke'])
                ->setName('languages.update.name.api');

            $languages->post('/update-code', [LanguagesUpdateCodeController::class, '__invoke'])
                ->setName('languages.update.code.api');

            // ─────────────────────────────
            // Languages translations Control
            // ─────────────────────────────
            $languages->group('/{language_id:[0-9]+}/translations', function (RouteCollectorProxyInterface $languagesTranslations) {
                $languagesTranslations->post('/query', [\Maatify\AdminKernel\Http\Controllers\Api\I18n\LanguageTranslationsQueryController::class, '__invoke'])
                    ->setName('languages.translations.list.api');

                $languagesTranslations->post('/upsert', [\Maatify\AdminKernel\Http\Controllers\Api\I18n\LanguageTranslationUpsertController::class, '__invoke'])
                    ->setName('languages.translations.upsert.api');

                $languagesTranslations->post('/delete', [\Maatify\AdminKernel\Http\Controllers\Api\I18n\LanguageTranslationDeleteController::class, '__invoke'])
                    ->setName('languages.translations.delete.api');
            });

        });
    }
}
