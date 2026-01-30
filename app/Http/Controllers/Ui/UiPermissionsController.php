<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ui;

use App\Context\AdminContext;
use App\Domain\Service\AuthorizationService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

readonly class UiPermissionsController
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
            'can_update_meta'  => $this->authorizationService->hasPermission($adminId, 'permissions.metadata.update'),
        ];
        return $this->view->render($response, 'pages/permissions.twig', [
            'capabilities' => $capabilities
        ]);
    }
}
