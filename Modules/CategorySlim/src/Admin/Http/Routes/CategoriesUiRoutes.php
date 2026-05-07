<?php

declare(strict_types=1);

namespace Maatify\CategorySlim\Admin\Http\Routes;

use Maatify\AdminKernel\Http\Middleware\AuthorizationGuardMiddleware;
use Maatify\CategorySlim\Admin\Http\Controllers\Ui\CategoriesListUiController;
use Maatify\CategorySlim\Admin\Http\Controllers\Ui\CategoryDetailUiController;
use Maatify\CategorySlim\Admin\Http\Controllers\Ui\CategoryImagesListUiController;
use Maatify\CategorySlim\Admin\Http\Controllers\Ui\CategorySubCategoriesListUiController;
use Maatify\CategorySlim\Admin\Http\Controllers\Ui\CategoryTranslationsListUiController;
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
                '/{category_id:[0-9]+}/sub-categories',
                [CategorySubCategoriesListUiController::class, '__invoke']
            )->setName('categories.sub_categories.list.ui');

            $categoriesGroup->get(
                '/{category_id:[0-9]+}/images',
                [CategoryImagesListUiController::class, '__invoke']
            )->setName('categories.images.list.ui');

            $categoriesGroup->get(
                '/{category_id:[0-9]+}/translations',
                [CategoryTranslationsListUiController::class, '__invoke']
            )->setName('categories.translations.list.ui');

        })->add(AuthorizationGuardMiddleware::class);
    }
}

