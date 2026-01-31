<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Ui;

use Maatify\AdminKernel\Http\Controllers\Web\LoginController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

readonly class UiLoginController
{
    public function __construct(
        private LoginController $webLogin
    ) {
    }

    public function index(Request $request, Response $response): Response
    {
        return $this->webLogin->index(
            $request->withAttribute('template', 'pages/login.twig'),
            $response
        );
    }

    public function login(Request $request, Response $response): Response
    {
        return $this->webLogin->login(
            $request->withAttribute('template', 'pages/login.twig'),
            $response
        );
    }
}
