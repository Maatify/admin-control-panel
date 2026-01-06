<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ui;

use App\Http\Controllers\Web\DashboardController;
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
        return $this->webDashboard->index(
            $request->withAttribute('template', 'pages/dashboard.twig'),
            $response
        );
    }
}
