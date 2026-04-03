<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Ui;

use Maatify\AdminKernel\Http\Controllers\Web\DashboardController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

readonly class UiDashboardController
{
    public function __construct(
        private DashboardController $webDashboard
    ) {
    }

    public function index(Request $request, Response $response): Response
    {
        // TODO: Introduce dashboard.view permission in RBAC strict mode
        return $this->webDashboard->index(
            $request->withAttribute('template', 'pages/dashboard.twig'),
            $response
        );
    }
}
