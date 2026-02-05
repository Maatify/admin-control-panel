<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Ui\Roles;

use Maatify\AdminKernel\Context\AdminContext;
use Maatify\AdminKernel\Domain\Contracts\Roles\RoleRepositoryInterface;
use Maatify\AdminKernel\Domain\Service\AuthorizationService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

readonly class UiRoleDetailsController
{
    public function __construct(
        private Twig $view,
        private AuthorizationService $authorizationService,
        private RoleRepositoryInterface $roleRepository,
    ) {
    }

    /**
     * Role Details View
     *
     * GET /roles/{id}
     *
     * @param array<string,string> $args
     */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        /** @var AdminContext $context */
        $context = $request->getAttribute(AdminContext::class);
        $adminId = $context->adminId;

        $roleId = (int) ($args['id'] ?? 0);

        // ─────────────────────────────
        // Load Role (throws if not found)
        // ─────────────────────────────
        $role = $this->roleRepository->getById($roleId);

        // ─────────────────────────────
        // Capabilities (UI visibility only)
        // ─────────────────────────────
        $capabilities = [
            // Overview
            'can_view_roles'            => $this->authorizationService->hasPermission($adminId, 'roles.query'),

            // Permissions tab
            'can_view_permissions'     => $this->authorizationService->hasPermission($adminId, 'roles.permissions.view'),
            'can_assign_permissions'   => $this->authorizationService->hasPermission($adminId, 'roles.permissions.assign'),
            'can_unassign_permissions'   => $this->authorizationService->hasPermission($adminId, 'roles.permissions.unassign'),

            // Admins tab (next phase)
            'can_view_admin_profile'           => $this->authorizationService->hasPermission($adminId, 'admins.profile.view'),
            'can_view_admins'           => $this->authorizationService->hasPermission($adminId, 'roles.admins.view'),
            'can_assign_admins'         => $this->authorizationService->hasPermission($adminId, 'roles.admins.assign'),
            'can_unassign_admins'         => $this->authorizationService->hasPermission($adminId, 'roles.admins.unassign'),
        ];

        return $this->view->render($response, 'pages/roles/details.twig', [
            'role'         => $role,
            'capabilities' => $capabilities,
        ]);
    }
}
