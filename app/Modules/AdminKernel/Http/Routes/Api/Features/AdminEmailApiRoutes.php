<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Routes\Api\Features;

use Maatify\AdminKernel\Http\Controllers\Api\Admin\AdminEmailVerificationController;
use Psr\Container\ContainerInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;

class AdminEmailApiRoutes
{
    /**
     * @param RouteCollectorProxyInterface<ContainerInterface> $group
     */
    public static function register(RouteCollectorProxyInterface $group): void
    {
        $group->group('/admin-emails', function (RouteCollectorProxyInterface $adminEmails) {
            $adminEmails->post('/{emailId:[0-9]+}/verify', [AdminEmailVerificationController::class, 'verify'])
                ->setName('admin.email.verify');
            $adminEmails->post('/{emailId:[0-9]+}/replace', [AdminEmailVerificationController::class, 'replace'])
                ->setName('admin.email.replace');
            $adminEmails->post('/{emailId:[0-9]+}/fail', [AdminEmailVerificationController::class, 'fail'])
                ->setName('admin.email.fail');
            $adminEmails->post('/{emailId}/restart-verification', [AdminEmailVerificationController::class, 'restart'])
                ->setName('admin.email.restart');
        });
    }
}
