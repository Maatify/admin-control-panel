<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Ui;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

// UI sandbox for Twig/layout experimentation (non-canonical page)
readonly class UiExamplesController
{
    public function __construct(
        private Twig $view
    ) {
    }

    public function index(Request $request, Response $response): Response
    {
        return $this->view->render($response, 'examples/main.twig');
    }
}
