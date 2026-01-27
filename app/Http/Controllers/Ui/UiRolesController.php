<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ui;

use App\Context\AdminContext;
use App\Domain\Service\AuthorizationService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

readonly class UiRolesController
{
    public function __construct(
        private Twig $view,
        private AuthorizationService $authorizationService,
    ) {
    }

    public function index(Request $request, Response $response): Response
    {
        /** @var AdminContext $context */
        $context = $request->getAttribute(AdminContext::class);
        $adminId = $context->adminId;

        $capabilities = [
            'can_create'       => $this->authorizationService->hasPermission($adminId, 'roles.create'),
            'can_update_meta'  => $this->authorizationService->hasPermission($adminId, 'roles.metadata.update'),
            'can_rename'       => $this->authorizationService->hasPermission($adminId, 'roles.rename'),
            'can_toggle'       => $this->authorizationService->hasPermission($adminId, 'roles.toggle'),
            'can_view_role'    => $this->authorizationService->hasPermission($adminId, 'roles.view'),
        ];
        return $this->view->render($response, 'pages/roles.twig', [
            'capabilities' => $capabilities
        ]);
    }
}
