<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Web;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

readonly class DashboardController
{
    public function __construct(
        private Twig $view
    ) {
    }

    public function index(Request $request, Response $response): Response
    {
        $template = $request->getAttribute('template');
        if (!is_string($template)) {
            $template = 'dashboard.twig';
        }
        return $this->view->render($response, $template);
    }
}
