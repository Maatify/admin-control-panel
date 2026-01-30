<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ui;

use App\Application\Admin\AdminProfileUpdateService;
use App\Context\AdminContext;
use App\Domain\Admin\Reader\AdminBasicInfoReaderInterface;
use App\Domain\Admin\Reader\AdminEmailReaderInterface;
use App\Domain\Admin\Reader\AdminProfileReaderInterface;
use App\Domain\Service\AuthorizationService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;
use Slim\Exception\HttpNotFoundException;
use Slim\Views\Twig;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

readonly class UiAdminsController
{
    public function __construct(
        private Twig $view,
        private AdminProfileReaderInterface $profileReader,
        private AdminProfileUpdateService $profileUpdateService,
        private AdminEmailReaderInterface $emailReader,
        private AdminBasicInfoReaderInterface $basicInfoReader,
        private AuthorizationService $authorizationService,
    ) {
    }

    public function index(Request $request, Response $response): Response
    {
        /** @var AdminContext $context */
        $context = $request->getAttribute(AdminContext::class);
        $adminId = $context->adminId;

        $capabilities = [
            'can_create'       => $this->authorizationService->hasPermission($adminId, 'admin.create.api'),
            'can_view_admin'  => $this->authorizationService->hasPermission($adminId, 'admins.profile.view'),
        ];
        return $this->view->render($response, 'pages/admins.twig', [
            'capabilities' => $capabilities
        ]);
    }

    /**
     * ===============================
     * Admin Profile — VIEW
     * GET /admins/{id}/profile
     * ===============================
     *
     * - Read-only
     * - No mutations
     * - No audit
     *
     * @param   Request                $request
     * @param   Response               $response
     * @param   array<string, string>  $args
     *
     * @return Response
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function profile(Request $request, Response $response, array $args): Response
    {
        $adminId = $this->extractAdminId($args);

        $profile = $this->profileReader->getProfile($adminId);

        $capabilities = [
            'can_edit'       => $this->authorizationService->hasPermission($adminId, 'admins.profile.edit'),
            'can_view_sessions'       => $this->authorizationService->hasPermission($adminId, 'sessions.list'),
            'can_view_emails'       => $this->authorizationService->hasPermission($adminId, 'admin.email.list'),
            'can_view_admins'       => $this->authorizationService->hasPermission($adminId, 'admins.list'),
            'can_view_notifications'       => $this->authorizationService->hasPermission($adminId, 'notifications.list'),
        ];
        $profile['capabilities'] = $capabilities;

        return $this->view->render(
            $response,
            'pages/admins_profile.twig',
            $profile
        );
    }

    /**
     * ===============================
     * Admin Profile — EDIT FORM
     * GET /admins/{id}/profile/edit
     * ===============================
     *
     * - Displays editable fields
     * - No mutations
     * - No audit
     *
     * @param   Request                $request
     * @param   Response               $response
     * @param   array<string, string>  $args
     *
     * @return Response
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function editProfile(Request $request, Response $response, array $args): Response
    {
        $adminId = $this->extractAdminId($args);

        // NOTE:
        // For now, we reuse the same reader.
        // Later we MAY introduce a dedicated EditReader if needed.
        $profile = $this->profileReader->getProfile($adminId);

        return $this->view->render(
            $response,
            'pages/admins_profile_edit.twig',
            $profile
        );
    }

    /**
     * ===============================
     * Admin Profile — UPDATE
     * POST /admins/{id}/profile/edit
     * ===============================
     *
     * - Partial update (display_name, status)
     * - No business logic here
     * - Audit & Activity handled by service
     * - Step-Up handled by middleware (later)
     *
     * @param   Request                $request
     * @param   Response               $response
     * @param   array<string, string>  $args
     *
     * @return Response
     */
    public function updateProfile(Request $request, Response $response, array $args): Response
    {
        $adminId = $this->extractAdminId($args);

        $parsedBody = $request->getParsedBody();

        if (!is_array($parsedBody)) {
            throw new RuntimeException('Invalid request body');
        }

        /**
         * Allowed fields only
         * (Any extra keys are ignored intentionally)
         */
        $input = [];

        if (array_key_exists('display_name', $parsedBody)) {
            $input['display_name'] = is_string($parsedBody['display_name'])
                ? trim($parsedBody['display_name'])
                : null;
        }

        if (array_key_exists('status', $parsedBody)) {
            if (!is_string($parsedBody['status'])) {
                throw new RuntimeException('Invalid status value');
            }

            $input['status'] = $parsedBody['status'];
        }

        // AdminContext + RequestContext are guaranteed by middleware
        $adminContext = $request->getAttribute(\App\Context\AdminContext::class);
        $requestContext = $request->getAttribute(\App\Context\RequestContext::class);

        if (
            !$adminContext instanceof \App\Context\AdminContext ||
            !$requestContext instanceof \App\Context\RequestContext
        ) {
            throw new RuntimeException('Context missing');
        }

        $this->profileUpdateService->update(
            adminContext: $adminContext,
            requestContext: $requestContext,
            targetAdminId: $adminId,
            input: $input
        );

        // Redirect back to profile view
        return $response
            ->withHeader('Location', "/admins/{$adminId}/profile")
            ->withStatus(302);
    }

    /**
     * ===============================
     * Admin Emails — LIST
     * GET /admins/{id}/emails
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
    public function emails(Request $request, Response $response, array $args): Response
    {
        $adminId = $this->extractAdminId($args);

        $displayName = $this->basicInfoReader->getDisplayName($adminId);

        if ($displayName === null) {
            throw new HttpNotFoundException($request, 'Admin not found');
        }

        $emails = $this->emailReader->listByAdminId($adminId);

        return $this->view->render(
            $response,
            'pages/admin/email.list.twig',
            [
                'admin_id' => $adminId,
                'display_name' => $displayName,
                'emails' => $emails,
            ]
        );
    }

    /**
     * ===============================
     * Admin Sessions — LIST
     * GET /admins/{id}/sessions
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
    public function sessions(Request $request, Response $response, array $args): Response
    {
        $adminId = $this->extractAdminId($args);

        $displayName = $this->basicInfoReader->getDisplayName($adminId);

        if ($displayName === null) {
            throw new HttpNotFoundException($request, 'Admin not found');
        }

        return $this->view->render(
            $response,
            'pages/admin/sessions.list.twig',
            [
                'admin_id' => $adminId,
                'display_name' => $displayName,
            ]
        );
    }

    /**
     * ===============================
     * Internal Helper
     * ===============================
     *
     * @param   array<string, string>  $args
    */
    private function extractAdminId(array $args): int
    {
        if (!isset($args['id']) || !ctype_digit((string) $args['id'])) {
            throw new RuntimeException('Invalid admin id');
        }

        return (int) $args['id'];
    }
}
