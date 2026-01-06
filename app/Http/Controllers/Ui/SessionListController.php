<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ui;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class SessionListController
{
    public function __construct(
        private Twig $twig
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        return $this->twig->render($response, 'pages/sessions.twig');
    }
}
