<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Ui\Permissions;

use Maatify\AdminKernel\Context\AdminContext;
use Maatify\AdminKernel\Domain\Contracts\Permissions\PermissionDetailsRepositoryInterface;
use Maatify\AdminKernel\Domain\Service\AuthorizationService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

readonly class UiAPermissionDetailsController
{
    public function __construct(
        private Twig $view,
        private AuthorizationService $authorizationService,
        private PermissionDetailsRepositoryInterface $repository
    ) {
    }

    /**
     * ===============================
     * Admin Permissions — LIST
     * GET /permissions/{id}
     * ===============================
     *
     * - Read-only
     * - UI only
     * - No mutations
     * - No audit
     *
     * @param   Request                $request
     * @param   Response               $response
     * @param   array<string, string>  $args
     *
     * @return Response
     */
    public function index(Request $request, Response $response, array $args): Response
    {
        $permission_id = (int)$args['permission_id'];

        /** @var AdminContext $context */
        $context = $request->getAttribute(AdminContext::class);
        $adminId = $context->adminId;

        $permission = $this->repository->getPermissionById($permission_id);

        // ─────────────────────────────
        // Capabilities (UI visibility only)
        // ─────────────────────────────
        $capabilities = [
            // permissions url (breadcrumbs) GET /permissions
            'can_view_permissions'            => $this->authorizationService->hasPermission($adminId, 'permissions.query.ui'),

            // Admins url (direct admin table) GET /admins/{id:[0-9]+}/profile
            'can_view_admin_profile'           => $this->authorizationService->hasPermission($adminId, 'admins.profile.view'),

            // roles url (direct role table) GET /roles/{id:[0-9]+}
            'can_view_role_details'                  => $this->authorizationService->hasPermission($adminId, 'roles.view.ui'),

            // roles tab (roles table) POST /api/permissions/{permissionId}/roles/query
            'can_view_roles_tab'                  => $this->authorizationService->hasPermission($adminId, 'permissions.roles.query'),

            // admin tab (admins table) POST /api/permissions/{permissionId}/admins/query
            'can_view_admins_tab'                  => $this->authorizationService->hasPermission($adminId, 'permissions.admins.query'),
        ];

        return $this->view->render($response, 'pages/permissions/permission_details.twig', [
            'permission'         => $permission->jsonSerialize(),
            'capabilities' => $capabilities,
        ]);
    }
}
