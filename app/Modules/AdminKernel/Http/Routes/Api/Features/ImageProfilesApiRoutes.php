<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Routes\Api\Features;

use Maatify\AdminKernel\Http\Controllers\Api\ImageProfile\ImageProfilesCreateController;
use Maatify\AdminKernel\Http\Controllers\Api\ImageProfile\ImageProfilesDetailsController;
use Maatify\AdminKernel\Http\Controllers\Api\ImageProfile\ImageProfilesDropdownController;
use Maatify\AdminKernel\Http\Controllers\Api\ImageProfile\ImageProfilesQueryController;
use Maatify\AdminKernel\Http\Controllers\Api\ImageProfile\ImageProfilesSetActiveController;
use Maatify\AdminKernel\Http\Controllers\Api\ImageProfile\ImageProfilesUpdateController;
use Psr\Container\ContainerInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;

final class ImageProfilesApiRoutes
{
    /**
     * @param RouteCollectorProxyInterface<ContainerInterface> $group
     */
    public static function register(RouteCollectorProxyInterface $group): void
    {
        $group->group('/image-profiles', function (RouteCollectorProxyInterface $profiles): void {
            $profiles->post('/dropdown', [ImageProfilesDropdownController::class, '__invoke'])
                ->setName('image_profiles.dropdown.api');

            $profiles->post('/query', [ImageProfilesQueryController::class, '__invoke'])
                ->setName('image_profiles.list.api');

            $profiles->post('/details', [ImageProfilesDetailsController::class, '__invoke'])
                ->setName('image_profiles.details.api');

            $profiles->post('/create', [ImageProfilesCreateController::class, '__invoke'])
                ->setName('image_profiles.create.api');

            $profiles->post('/update', [ImageProfilesUpdateController::class, '__invoke'])
                ->setName('image_profiles.update.api');

            $profiles->post('/set-active', [ImageProfilesSetActiveController::class, '__invoke'])
                ->setName('image_profiles.set_active.api');
        });
    }
}
