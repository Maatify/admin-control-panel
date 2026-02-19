<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Routes\Ui;

use Maatify\AdminKernel\Http\Middleware\SessionGuardMiddleware;
use Maatify\AdminKernel\Http\Middleware\WebGuestGuardMiddleware;
use Psr\Container\ContainerInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;

final class UiRoutes
{
    /**
     * @param RouteCollectorProxyInterface<ContainerInterface> $app
     */
    public static function register(RouteCollectorProxyInterface $app): void
    {
        // User-facing UI Routes (Clean URLs)
        $app->group('', function (RouteCollectorProxyInterface $group) {

            // Guest Routes
            $group->group('', function (RouteCollectorProxyInterface $guestGroup) {
                $guestGroup->get('/login', [\Maatify\AdminKernel\Http\Controllers\Ui\Auth\UiLoginController::class, 'index']);

                $guestGroup->post('/login', [\Maatify\AdminKernel\Http\Controllers\Ui\Auth\UiLoginController::class, 'login'])
                    ->add(\Maatify\AbuseProtection\Middleware\AbuseProtectionMiddleware::class)
                    ->add(\Maatify\AdminKernel\Http\Middleware\AbuseCookieReaderMiddleware::class);

                $guestGroup->get('/verify-email', [\Maatify\AdminKernel\Http\Controllers\Ui\Auth\UiVerificationController::class, 'index']);
                $guestGroup->post('/verify-email', [\Maatify\AdminKernel\Http\Controllers\Ui\Auth\UiVerificationController::class, 'verify']);
                $guestGroup->post('/verify-email/resend', [\Maatify\AdminKernel\Http\Controllers\Ui\Auth\UiVerificationController::class, 'resend']);

                $guestGroup->get('/error', [\Maatify\AdminKernel\Http\Controllers\Ui\UiErrorController::class, 'index']);

                // Change Password (Forced / Initial)
                $guestGroup->get(
                    '/auth/change-password',
                    [\Maatify\AdminKernel\Http\Controllers\Web\ChangePasswordController::class, 'index']
                );

                $guestGroup->post(
                    '/auth/change-password',
                    [\Maatify\AdminKernel\Http\Controllers\Web\ChangePasswordController::class, 'change']
                );
            })->add(WebGuestGuardMiddleware::class);

            // Step-Up Verification (Session only, no Active check)
            $group->group('', function (RouteCollectorProxyInterface $stepUpGroup) {
                $stepUpGroup->get('/2fa/verify', [\Maatify\AdminKernel\Http\Controllers\Ui\Auth\UiStepUpController::class, 'verify']);
                $stepUpGroup->post('/2fa/verify', [\Maatify\AdminKernel\Http\Controllers\Ui\Auth\UiStepUpController::class, 'doVerify']);
            })
                ->add(\Maatify\AdminKernel\Http\Middleware\AdminContextMiddleware::class)
                ->add(SessionGuardMiddleware::class);

            // Protected UI Routes (Dashboard)
            UiProtectedRoutes::register($group);

        })->add(\Maatify\AdminKernel\Http\Middleware\UiRedirectNormalizationMiddleware::class);
    }
}
