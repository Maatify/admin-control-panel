<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Ui\Roles;

use Maatify\AdminKernel\Application\Security\UiPermissionService;

use Maatify\AdminKernel\Context\AdminContext;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

readonly class UiRolesController
{
    public function __construct(
        private Twig $view,
        private UiPermissionService $uiPermissionService,
    ) {
    }

    public function index(Request $request, Response $response): Response
    {
        /** @var AdminContext $context */
        $context = $request->getAttribute(AdminContext::class);
        $adminId = $context->adminId;

        $capabilities = [
            'can_create'       => $this->uiPermissionService->hasPermission($adminId, 'roles.create'),
            'can_update_meta'  => $this->uiPermissionService->hasPermission($adminId, 'roles.metadata.update'),
            'can_rename'       => $this->uiPermissionService->hasPermission($adminId, 'roles.rename'),
            'can_toggle'       => $this->uiPermissionService->hasPermission($adminId, 'roles.toggle'),
            'can_view_role'    => $this->uiPermissionService->hasPermission($adminId, 'roles.view'),
        ];
        return $this->view->render($response, 'pages/roles.twig', [
            'capabilities' => $capabilities
        ]);
    }
}
