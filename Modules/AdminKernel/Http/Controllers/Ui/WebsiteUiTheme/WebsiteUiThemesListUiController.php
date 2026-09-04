<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Ui\WebsiteUiTheme;

use Maatify\AdminKernel\Application\Security\UiPermissionService;
use Maatify\AdminKernel\Context\AdminContext;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final readonly class WebsiteUiThemesListUiController
{
    public function __construct(
        private Twig $twig,
        private UiPermissionService $uiPermissionService,
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        /** @var AdminContext $context */
        $context = $request->getAttribute(AdminContext::class);
        $adminId = $context->adminId;

        $capabilities = [
            'can_create' => $this->uiPermissionService->hasPermission($adminId, 'website_ui_themes.create'),
            'can_update' => $this->uiPermissionService->hasPermission($adminId, 'website_ui_themes.update'),
            'can_delete' => $this->uiPermissionService->hasPermission($adminId, 'website_ui_themes.delete'),
        ];

        return $this->twig->render(
            $response,
            'pages/website_ui_themes/website_ui_themes_list.twig',
            [
                'capabilities' => $capabilities,
            ]
        );
    }
}
