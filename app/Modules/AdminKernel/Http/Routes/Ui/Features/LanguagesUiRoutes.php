<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Routes\Ui\Features;

use Maatify\AdminKernel\Http\Controllers\Ui\LanguagesListController;
use Maatify\AdminKernel\Http\Middleware\AuthorizationGuardMiddleware;
use Slim\Interfaces\RouteCollectorProxyInterface;

final class LanguagesUiRoutes
{
    public static function register(RouteCollectorProxyInterface $group): void
    {
        $group->group('/languages', function (RouteCollectorProxyInterface $languagesGroup) {

            $languagesGroup->get('', [LanguagesListController::class, '__invoke'])
                ->setName('languages.list.ui');

            $languagesGroup->get(
                '/{language_id:[0-9]+}/translations',
                [\Maatify\AdminKernel\Http\Controllers\Ui\I18n\LanguageTranslationsListUiController::class, '__invoke']
            )
                ->setName('languages.translations.list.ui');

        })->add(AuthorizationGuardMiddleware::class);
    }
}
