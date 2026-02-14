<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-05 10:53
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Ui\AppSettings;

use Maatify\AdminKernel\Context\AdminContext;
use Maatify\AdminKernel\Domain\Service\AuthorizationService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final readonly class AppSettingsListUiController
{
    public function __construct(
        private Twig $twig,
        private AuthorizationService $authorization
    )
    {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        /** @var AdminContext $context */
        $context = $request->getAttribute(AdminContext::class);
        $adminId = $context->adminId;

        $capabilities = [
            'can_create' => $this->authorization->hasPermission($adminId, 'app_settings.create'),
            'can_update' => $this->authorization->hasPermission($adminId, 'app_settings.update'),
            'can_set_active' => $this->authorization->hasPermission($adminId, 'app_settings.update'),
        ];

        return $this->twig->render($response, 'pages/app-settings/app_settings.list.twig', [
            'capabilities' => $capabilities,
        ]);
    }
}
