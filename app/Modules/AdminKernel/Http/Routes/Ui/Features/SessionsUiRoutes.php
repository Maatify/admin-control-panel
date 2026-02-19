<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Routes\Ui\Features;

use Maatify\AdminKernel\Http\Middleware\AuthorizationGuardMiddleware;
use Slim\Interfaces\RouteCollectorProxyInterface;

final class SessionsUiRoutes
{
    public static function register(RouteCollectorProxyInterface $group): void
    {
        $group->get(
            '/sessions',
            [\Maatify\AdminKernel\Http\Controllers\Ui\SessionListController::class, '__invoke']
        )
            ->setName('sessions.list.ui')
            ->add(AuthorizationGuardMiddleware::class);
    }
}
