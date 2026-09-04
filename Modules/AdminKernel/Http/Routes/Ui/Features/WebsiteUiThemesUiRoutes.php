<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Routes\Ui\Features;

use Maatify\AdminKernel\Http\Controllers\Ui\WebsiteUiTheme\WebsiteUiThemesListUiController;
use Maatify\AdminKernel\Http\Middleware\AuthorizationGuardMiddleware;
use Psr\Container\ContainerInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;

final class WebsiteUiThemesUiRoutes
{
    /**
     * @param RouteCollectorProxyInterface<ContainerInterface> $group
     */
    public static function register(RouteCollectorProxyInterface $group): void
    {
        $group->group('/website-ui-themes', function (RouteCollectorProxyInterface $themesGroup): void {
            $themesGroup->get('', [WebsiteUiThemesListUiController::class, '__invoke'])
                ->setName('website_ui_themes.list.ui');
        })->add(AuthorizationGuardMiddleware::class);
    }
}
