<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Routes\Api;

use Maatify\AdminKernel\Http\Controllers\NotificationQueryController;
use Maatify\AdminKernel\Http\Middleware\AuthorizationGuardMiddleware;
use Maatify\AdminKernel\Http\Middleware\SessionGuardMiddleware;
use Maatify\AdminKernel\Http\Routes\Api\Features\AdminEmailApiRoutes;
use Maatify\AdminKernel\Http\Routes\Api\Features\AdminsApiRoutes;
use Maatify\AdminKernel\Http\Routes\Api\Features\AppSettingsApiRoutes;
use Maatify\AdminKernel\Http\Routes\Api\Features\ContentDocumentsApiRoutes;
use Maatify\AdminKernel\Http\Routes\Api\Features\I18nApiRoutes;
use Maatify\AdminKernel\Http\Routes\Api\Features\LanguagesApiRoutes;
use Maatify\AdminKernel\Http\Routes\Api\Features\PermissionsApiRoutes;
use Maatify\AdminKernel\Http\Routes\Api\Features\RolesApiRoutes;
use Maatify\AdminKernel\Http\Routes\Api\Features\SessionsApiRoutes;
use Psr\Container\ContainerInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;

final class ApiProtectedRoutes
{
    /**
     * @param RouteCollectorProxyInterface<ContainerInterface> $api
     */
    public static function register(RouteCollectorProxyInterface $api): void
    {
        $api->group('', function (RouteCollectorProxyInterface $group) {

            AdminEmailApiRoutes::register($group);

            AdminsApiRoutes::register($group);

            SessionsApiRoutes::register($group);

            ContentDocumentsApiRoutes::register($group);

            I18nApiRoutes::register($group);

            AppSettingsApiRoutes::register($group);

            LanguagesApiRoutes::register($group);

            PermissionsApiRoutes::register($group);

            RolesApiRoutes::register($group);

            $group->get('/notifications', [NotificationQueryController::class, 'index'])
                ->setName('notifications.list');

            $group->post('/admin/notifications/{id}/read', [\Maatify\AdminKernel\Http\Controllers\AdminNotificationReadController::class, 'markAsRead'])
                ->setName('admin.notifications.read');

        })
            // NOTE [Slim Middleware Order]:
            // Slim executes middlewares in LIFO order (last added = first executed).
            // This ordering is intentional so AdminContextMiddleware runs
            // BEFORE TwigAdminContextMiddleware, allowing Twig to safely
            // consume AdminContext and expose `current_admin` as a global.
            ->add(AuthorizationGuardMiddleware::class)
            ->add(\Maatify\AdminKernel\Http\Middleware\ScopeGuardMiddleware::class)
            ->add(\Maatify\AdminKernel\Http\Middleware\SessionStateGuardMiddleware::class)
            ->add(\Maatify\AdminKernel\Http\Middleware\AdminContextMiddleware::class)
            ->add(SessionGuardMiddleware::class);
    }
}
