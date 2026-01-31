<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-19 10:55
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Ui;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

readonly class UiAdminCreateController
{
    public function __construct(
        private Twig $view
    ) {
    }

    /**
     * GET /admins/create
     *
     * Preconditions (enforced by middleware):
     * - Valid session
     * - SessionState::ACTIVE
     * - Step-Up grant for scope "admin.create"
     */
    public function index(Request $request, Response $response): Response
    {
        return $this->view->render(
            $response,
            'pages/admins_create.twig'
        );
    }
}
