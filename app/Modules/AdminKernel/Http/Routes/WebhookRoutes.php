<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Routes;

use Slim\Interfaces\RouteCollectorProxyInterface;

final class WebhookRoutes
{
    public static function register(RouteCollectorProxyInterface $app): void
    {
        // Webhooks
        $app->post('/webhooks/telegram', [\Maatify\AdminKernel\Http\Controllers\TelegramWebhookController::class, 'handle']);
    }
}
