<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Routes\Api\Features;

use Maatify\AdminKernel\Http\Controllers\Api\WebsiteUiTheme\WebsiteUiThemesCreateController;
use Maatify\AdminKernel\Http\Controllers\Api\WebsiteUiTheme\WebsiteUiThemesDetailsController;
use Maatify\AdminKernel\Http\Controllers\Api\WebsiteUiTheme\WebsiteUiThemesDropdownByEntityTypeController;
use Maatify\AdminKernel\Http\Controllers\Api\WebsiteUiTheme\WebsiteUiThemesDropdownController;
use Maatify\AdminKernel\Http\Controllers\Api\WebsiteUiTheme\WebsiteUiThemesQueryController;
use Maatify\AdminKernel\Http\Controllers\Api\WebsiteUiTheme\WebsiteUiThemesUpdateController;
use Psr\Container\ContainerInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;

final class WebsiteUiThemesApiRoutes
{
    /**
     * @param RouteCollectorProxyInterface<ContainerInterface> $group
     */
    public static function register(RouteCollectorProxyInterface $group): void
    {
        $group->group('/website-ui-themes', function (RouteCollectorProxyInterface $themes): void {
            $themes->post('/dropdown', [WebsiteUiThemesDropdownController::class, '__invoke'])
                ->setName('website_ui_themes.dropdown.api');

            $themes->post('/dropdown-by-entity-type', [WebsiteUiThemesDropdownByEntityTypeController::class, '__invoke'])
                ->setName('website_ui_themes.dropdown_by_entity_type.api');

            $themes->post('/query', [WebsiteUiThemesQueryController::class, '__invoke'])
                ->setName('website_ui_themes.list.api');

            $themes->post('/details', [WebsiteUiThemesDetailsController::class, '__invoke'])
                ->setName('website_ui_themes.details.api');

            $themes->post('/create', [WebsiteUiThemesCreateController::class, '__invoke'])
                ->setName('website_ui_themes.create.api');

            $themes->post('/update', [WebsiteUiThemesUpdateController::class, '__invoke'])
                ->setName('website_ui_themes.update.api');
        });
    }
}
