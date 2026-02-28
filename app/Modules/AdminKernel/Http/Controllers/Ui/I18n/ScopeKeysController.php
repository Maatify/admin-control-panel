<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-10 17:41
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Ui\I18n;

use Maatify\AdminKernel\Context\AdminContext;
use Maatify\AdminKernel\Domain\I18n\Scope\Reader\I18nScopeDetailsRepositoryInterface;
use Maatify\AdminKernel\Domain\Service\AuthorizationService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final readonly class ScopeKeysController
{
    public function __construct(
        private Twig $view,
        private AuthorizationService $authorizationService,
        private I18nScopeDetailsRepositoryInterface $repository
    )
    {
    }

    /**
     * ===============================
     * I18n Scope — KEYS (UI)
     * GET /i18n/scopes/{scope_id}/keys
     * ===============================
     *
     * - Read-only page
     * - UI only (Twig)
     * - No mutations
     * - Assign / unassign handled via API
     * - No audit logging
     *
     * @param   Request                $request
     * @param   Response               $response
     * @param   array<string, string>  $args
     *
     * @return Response
     */
    public function index(Request $request, Response $response, array $args): Response
    {
        $scopeId = (int)$args['scope_id'];

        /** @var AdminContext $context */
        $context = $request->getAttribute(AdminContext::class);
        $adminId = $context->adminId;

        $scope = $this->repository->getScopeDetailsById($scopeId);

        // ─────────────────────────────
        // Capabilities (UI visibility only)
        // ─────────────────────────────
        $capabilities = [
            'can_create' => $this->authorizationService->hasPermission(
                $adminId,
                'i18n.scopes.keys.create'
            ),

//            'can_delete' => $this->authorizationService->hasPermission(
//                $adminId,
//                'i18n.scopes.keys.delete'
//            ),

            'can_rename' => $this->authorizationService->hasPermission(
                $adminId,
                'i18n.scopes.keys.rename'
            ),

            'can_update_meta'   => $this->authorizationService->hasPermission(
                $adminId,
                'i18n.scopes.keys.update_metadata'
            ),

            'can_view_scopes' => $this->authorizationService->hasPermission(
                $adminId,
                'i18n.scopes.list'
            ),
        ];

        return $this->view->render($response, 'pages/i18n/scope_keys.twig', [
            'scope'        => $scope->jsonSerialize(),
            'capabilities' => $capabilities,
        ]);
    }
}
