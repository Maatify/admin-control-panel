<?php

declare(strict_types=1);

namespace Maatify\CategorySlim\Admin\Http\Routes;

use Maatify\CategorySlim\Admin\Http\Controllers\Api\CategoriesCreateController;
use Maatify\CategorySlim\Admin\Http\Controllers\Api\CategoriesDropdownController;
use Maatify\CategorySlim\Admin\Http\Controllers\Api\CategoriesQueryController;
use Maatify\CategorySlim\Admin\Http\Controllers\Api\CategoriesSetActiveController;
use Maatify\CategorySlim\Admin\Http\Controllers\Api\CategoriesUpdateController;
use Maatify\CategorySlim\Admin\Http\Controllers\Api\CategoriesUpdateSortOrderController;
use Maatify\CategorySlim\Admin\Http\Controllers\Api\Images\CategoryImageDeleteController;
use Maatify\CategorySlim\Admin\Http\Controllers\Api\Images\CategoryImagesQueryController;
use Maatify\CategorySlim\Admin\Http\Controllers\Api\Images\CategoryImageUpsertController;
use Maatify\CategorySlim\Admin\Http\Controllers\Api\Settings\CategorySettingDeleteController;
use Maatify\CategorySlim\Admin\Http\Controllers\Api\Settings\CategorySettingsQueryController;
use Maatify\CategorySlim\Admin\Http\Controllers\Api\Settings\CategorySettingUpsertController;
use Maatify\CategorySlim\Admin\Http\Controllers\Api\SubCategories\SubCategoriesDropdownController;
use Maatify\CategorySlim\Admin\Http\Controllers\Api\SubCategories\SubCategoriesQueryController;
use Maatify\CategorySlim\Admin\Http\Controllers\Api\Translations\CategoryTranslationDeleteController;
use Maatify\CategorySlim\Admin\Http\Controllers\Api\Translations\CategoryTranslationsQueryController;
use Maatify\CategorySlim\Admin\Http\Controllers\Api\Translations\CategoryTranslationUpsertController;
use Psr\Container\ContainerInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;

class CategoriesApiRoutes
{
    /**
     * @param RouteCollectorProxyInterface<ContainerInterface> $group
     */
    public static function register(RouteCollectorProxyInterface $group): void
    {
        $group->group('/categories', function (RouteCollectorProxyInterface $categories) {

            // ─────────────────────────────
            // Categories
            // ─────────────────────────────

            $categories->post('/dropdown', [CategoriesDropdownController::class, '__invoke'])
                ->setName('categories.dropdown.api');

            $categories->post('/query', [CategoriesQueryController::class, '__invoke'])
                ->setName('categories.list.api');

            $categories->post('/create', [CategoriesCreateController::class, '__invoke'])
                ->setName('categories.create.api');

            $categories->post('/update', [CategoriesUpdateController::class, '__invoke'])
                ->setName('categories.update.api');

            $categories->post('/set-active', [CategoriesSetActiveController::class, '__invoke'])
                ->setName('categories.set_active.api');

            $categories->post('/update-sort', [CategoriesUpdateSortOrderController::class, '__invoke'])
                ->setName('categories.update_sort.api');

            // ─────────────────────────────
            // Sub-categories (scoped by parent)
            // ─────────────────────────────
            $categories->group('/{category_id:[0-9]+}/sub-categories', function (RouteCollectorProxyInterface $sub) {

                $sub->post('/query', [SubCategoriesQueryController::class, '__invoke'])
                    ->setName('categories.sub_categories.list.api');

                $sub->post('/dropdown', [SubCategoriesDropdownController::class, '__invoke'])
                    ->setName('categories.sub_categories.dropdown.api');
            });

            // ─────────────────────────────
            // Category settings (scoped by category)
            // ─────────────────────────────
            $categories->group('/{category_id:[0-9]+}/settings', function (RouteCollectorProxyInterface $settings) {

                $settings->post('/query', [CategorySettingsQueryController::class, '__invoke'])
                    ->setName('categories.settings.list.api');

                $settings->post('/upsert', [CategorySettingUpsertController::class, '__invoke'])
                    ->setName('categories.settings.upsert.api');

                $settings->post('/delete', [CategorySettingDeleteController::class, '__invoke'])
                    ->setName('categories.settings.delete.api');
            });

            // ─────────────────────────────
            // Category images (scoped by category)
            // ─────────────────────────────
            $categories->group('/{category_id:[0-9]+}/images', function (RouteCollectorProxyInterface $images) {

                $images->post('/query', [CategoryImagesQueryController::class, '__invoke'])
                    ->setName('categories.images.list.api');

                $images->post('/upsert', [CategoryImageUpsertController::class, '__invoke'])
                    ->setName('categories.images.upsert.api');

                $images->post('/delete', [CategoryImageDeleteController::class, '__invoke'])
                    ->setName('categories.images.delete.api');
            });

            // ─────────────────────────────
            // Category translations (scoped by category)
            // ─────────────────────────────
            $categories->group('/{category_id:[0-9]+}/translations', function (RouteCollectorProxyInterface $translations) {

                $translations->post('/query', [CategoryTranslationsQueryController::class, '__invoke'])
                    ->setName('categories.translations.list.api');

                $translations->post('/upsert', [CategoryTranslationUpsertController::class, '__invoke'])
                    ->setName('categories.translations.upsert.api');

                $translations->post('/delete', [CategoryTranslationDeleteController::class, '__invoke'])
                    ->setName('categories.translations.delete.api');
            });

        });
    }
}

