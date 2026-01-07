<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ui;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

readonly class UiAdminsController
{
    public function __construct(
        private Twig $view
    ) {
    }

    public function list(Request $request, Response $response): Response
    {
        return $this->view->render($response, 'pages/admins.twig');
    }

    public function create(Request $request, Response $response): Response
    {
        return $this->view->render($response, 'pages/admin_create.twig');
    }

    public function edit(Request $request, Response $response, array $args): Response
    {
        return $this->view->render($response, 'pages/admin_edit.twig', ['target_admin_id' => $args['id']]);
    }
}
