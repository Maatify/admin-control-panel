<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Routes\Ui\Features;

use Maatify\AdminKernel\Http\Controllers\Ui\MyProfileController;
use Psr\Container\ContainerInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;

final class MyProfileUiRoutes
{
    /**
     * @param RouteCollectorProxyInterface<ContainerInterface> $group
     */
    public static function register(RouteCollectorProxyInterface $group): void
    {
        $group->group('/me', function (RouteCollectorProxyInterface $myProfileGroup) {
            $myProfileGroup->get('/profile', [MyProfileController::class, 'index'])
                ->setName('me.profile.view');

            $myProfileGroup->get('/password', [MyProfileController::class, 'changePasswordForm'])
                ->setName('me.password.view');

            $myProfileGroup->post('/password', [MyProfileController::class, 'changePasswordSubmit'])
                ->setName('me.password.submit');
        });
        // Note: No AuthorizationGuardMiddleware here because this should be accessible to any logged-in admin.
    }
}
