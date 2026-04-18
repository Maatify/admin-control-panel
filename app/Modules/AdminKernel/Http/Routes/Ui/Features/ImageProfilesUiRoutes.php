<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Routes\Ui\Features;

use Maatify\AdminKernel\Http\Controllers\Ui\ImageProfile\ImageProfilesListUiController;
use Maatify\AdminKernel\Http\Middleware\AuthorizationGuardMiddleware;
use Psr\Container\ContainerInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;

final class ImageProfilesUiRoutes
{
    /**
     * @param RouteCollectorProxyInterface<ContainerInterface> $group
     */
    public static function register(RouteCollectorProxyInterface $group): void
    {
        $group->group('/image-profiles', function (RouteCollectorProxyInterface $profilesGroup): void {
            $profilesGroup->get('', [ImageProfilesListUiController::class, '__invoke'])
                ->setName('image_profiles.list.ui');
        })->add(AuthorizationGuardMiddleware::class);
    }
}
