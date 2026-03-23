<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Ui;

use Maatify\AdminKernel\Context\AdminContext;
use Maatify\AdminKernel\Domain\Service\AuthorizationService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

readonly class SessionListController
{
    public function __construct(
        private Twig $twig,
        private UiPermissionService $uiPermissionService,
    )
    {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        /** @var AdminContext $context */
        $context = $request->getAttribute(AdminContext::class);
        $adminId = $context->adminId;

        $capabilities = [
            'can_revoke_id'   => $this->uiPermissionService->hasPermission($adminId, 'sessions.revoke.id'),
            'can_revoke_bulk' => $this->uiPermissionService->hasPermission($adminId, 'sessions.revoke.bulk'),
            'can_view_admin'  => $this->uiPermissionService->hasPermission($adminId, 'admins.profile.view'),
        ];

        return $this->twig->render($response, 'pages/sessions.twig', [
            'capabilities' => $capabilities,
        ]);
    }
}
