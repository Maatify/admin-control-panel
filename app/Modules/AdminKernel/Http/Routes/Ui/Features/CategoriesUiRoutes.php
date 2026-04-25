<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Routes\Ui\Features;

use Maatify\AdminKernel\Http\Controllers\Ui\Category\CategoriesListUiController;
use Maatify\AdminKernel\Http\Controllers\Ui\Category\CategoryDetailUiController;
use Maatify\AdminKernel\Http\Controllers\Ui\Category\CategoryTranslationsListUiController;
use Maatify\AdminKernel\Http\Middleware\AuthorizationGuardMiddleware;
use Psr\Container\ContainerInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;

final class CategoriesUiRoutes
{
    /**
     * @param RouteCollectorProxyInterface<ContainerInterface> $group
     */
    public static function register(RouteCollectorProxyInterface $group): void
    {
        $group->group('/categories', function (RouteCollectorProxyInterface $categoriesGroup) {

            $categoriesGroup->get('', [CategoriesListUiController::class, '__invoke'])
                ->setName('categories.list.ui');

            $categoriesGroup->get('/{category_id:[0-9]+}', [CategoryDetailUiController::class, '__invoke'])
                ->setName('categories.detail.ui');

            $categoriesGroup->get(
                '/{category_id:[0-9]+}/translations',
                [CategoryTranslationsListUiController::class, '__invoke']
            )->setName('categories.translations.list.ui');

        })->add(AuthorizationGuardMiddleware::class);
    }
}
