<?php

declare(strict_types=1);

namespace Maatify\SettingsSlim\Admin\Http\Controllers\Ui;

use Maatify\AdminKernel\Application\Security\UiPermissionService;
use Maatify\AdminKernel\Context\AdminContext;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final readonly class SettingsListUiController
{
    public function __construct(
        private Twig $twig,
        private UiPermissionService $uiPermissionService,
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        /** @var AdminContext $context */
        $context = $request->getAttribute(AdminContext::class);
        $adminId = $context->adminId;

        $capabilities = [
            'can_view' => $this->uiPermissionService->hasPermission($adminId, 'settings.get.api'),
            'can_edit' => $this->uiPermissionService->hasPermission($adminId, 'settings.update.api'),
        ];

        return $this->twig->render(
            $response,
            'pages/settings/settings_list.twig',
            [
                'capabilities' => $capabilities,
            ]
        );
    }
}
