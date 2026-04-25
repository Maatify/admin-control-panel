<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Routes\Api\Features;

use Maatify\AdminKernel\Http\Controllers\Api\Category\CategoriesCreateController;
use Maatify\AdminKernel\Http\Controllers\Api\Category\CategoriesDropdownController;
use Maatify\AdminKernel\Http\Controllers\Api\Category\CategoriesQueryController;
use Maatify\AdminKernel\Http\Controllers\Api\Category\CategoriesSetActiveController;
use Maatify\AdminKernel\Http\Controllers\Api\Category\CategoriesUpdateController;
use Maatify\AdminKernel\Http\Controllers\Api\Category\CategoriesUpdateSortOrderController;
use Maatify\AdminKernel\Http\Controllers\Api\Category\Images\CategoryImageDeleteController;
use Maatify\AdminKernel\Http\Controllers\Api\Category\Images\CategoryImagesQueryController;
use Maatify\AdminKernel\Http\Controllers\Api\Category\Images\CategoryImageUpsertController;
use Maatify\AdminKernel\Http\Controllers\Api\Category\Settings\CategorySettingDeleteController;
use Maatify\AdminKernel\Http\Controllers\Api\Category\Settings\CategorySettingsQueryController;
use Maatify\AdminKernel\Http\Controllers\Api\Category\Settings\CategorySettingUpsertController;
use Maatify\AdminKernel\Http\Controllers\Api\Category\SubCategories\SubCategoriesDropdownController;
use Maatify\AdminKernel\Http\Controllers\Api\Category\SubCategories\SubCategoriesQueryController;
use Maatify\AdminKernel\Http\Controllers\Api\Category\Translations\CategoryTranslationDeleteController;
use Maatify\AdminKernel\Http\Controllers\Api\Category\Translations\CategoryTranslationsQueryController;
use Maatify\AdminKernel\Http\Controllers\Api\Category\Translations\CategoryTranslationUpsertController;
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

