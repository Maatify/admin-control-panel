<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Routes\Api\Features;

use Psr\Container\ContainerInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;

class SessionsApiRoutes
{
    /**
     * @param RouteCollectorProxyInterface<ContainerInterface> $group
     */
    public static function register(RouteCollectorProxyInterface $group): void
    {
        // Phase 14.3: Sessions Query
        $group->group('/sessions', function (RouteCollectorProxyInterface $sessions) {
            $sessions->post('/query', [\Maatify\AdminKernel\Http\Controllers\Api\Sessions\SessionQueryController::class, '__invoke'])
                ->setName('sessions.list.api');

            $sessions->delete('/{session_id}', [\Maatify\AdminKernel\Http\Controllers\Api\Sessions\SessionRevokeController::class, '__invoke'])
                ->setName('sessions.revoke.id');

            $sessions->post('/revoke-bulk', [\Maatify\AdminKernel\Http\Controllers\Api\Sessions\SessionBulkRevokeController::class, '__invoke'])
                ->setName('sessions.revoke.bulk');
        });
    }
}
