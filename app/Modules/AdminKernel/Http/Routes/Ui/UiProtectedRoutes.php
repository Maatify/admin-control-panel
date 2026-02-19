<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Routes\Ui;

use Maatify\AdminKernel\Http\Routes\Ui\Features\ActivityLogsUiRoutes;
use Maatify\AdminKernel\Http\Routes\Ui\Features\AdminsUiRoutes;
use Maatify\AdminKernel\Http\Routes\Ui\Features\AppSettingsUiRoutes;
use Maatify\AdminKernel\Http\Routes\Ui\Features\ContentDocumentsUiRoutes;
use Maatify\AdminKernel\Http\Routes\Ui\Features\DashboardUiRoutes;
use Maatify\AdminKernel\Http\Routes\Ui\Features\I18nUiRoutes;
use Maatify\AdminKernel\Http\Routes\Ui\Features\LanguagesUiRoutes;
use Maatify\AdminKernel\Http\Routes\Ui\Features\LogoutUiRoutes;
use Maatify\AdminKernel\Http\Routes\Ui\Features\PermissionsUiRoutes;
use Maatify\AdminKernel\Http\Routes\Ui\Features\RolesUiRoutes;
use Maatify\AdminKernel\Http\Routes\Ui\Features\SessionsUiRoutes;
use Maatify\AdminKernel\Http\Routes\Ui\Features\SettingsUiRoutes;
use Maatify\AdminKernel\Http\Routes\Ui\Features\TelemetryUiRoutes;
use Maatify\AdminKernel\Http\Routes\Ui\Features\TwoFactorUiRoutes;
use Maatify\AdminKernel\Http\Middleware\SessionGuardMiddleware;
use Psr\Container\ContainerInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;

final class UiProtectedRoutes
{
    /**
     * @param RouteCollectorProxyInterface<ContainerInterface> $group
     */
    public static function register(RouteCollectorProxyInterface $group): void
    {
        $group->group('', function (RouteCollectorProxyInterface $protectedGroup) {

            DashboardUiRoutes::register($protectedGroup);
            TwoFactorUiRoutes::register($protectedGroup);

            ContentDocumentsUiRoutes::register($protectedGroup);

            AdminsUiRoutes::register($protectedGroup);

            I18nUiRoutes::register($protectedGroup);

            PermissionsUiRoutes::register($protectedGroup);
            RolesUiRoutes::register($protectedGroup);

            LanguagesUiRoutes::register($protectedGroup);

            AppSettingsUiRoutes::register($protectedGroup);
            SettingsUiRoutes::register($protectedGroup);

            SessionsUiRoutes::register($protectedGroup);

            ActivityLogsUiRoutes::register($protectedGroup);
            TelemetryUiRoutes::register($protectedGroup);

            LogoutUiRoutes::register($protectedGroup);

        })
            // NOTE [Slim Middleware Order]:
            // Slim executes middlewares in LIFO order (last added = first executed).
            // This ordering is intentional so AdminContextMiddleware runs
            // BEFORE TwigAdminContextMiddleware, allowing Twig to safely
            // consume AdminContext and expose `current_admin` as a global.
            ->add(\Maatify\AdminKernel\Http\Middleware\TwigAdminContextMiddleware::class)
            ->add(\Maatify\AdminKernel\Http\Middleware\ScopeGuardMiddleware::class)
            ->add(\Maatify\AdminKernel\Http\Middleware\SessionStateGuardMiddleware::class)
            ->add(\Maatify\AdminKernel\Http\Middleware\AdminContextMiddleware::class)
            ->add(SessionGuardMiddleware::class);
        //                ->add(\Maatify\AdminKernel\Http\Middleware\RememberMeMiddleware::class);
    }
}
