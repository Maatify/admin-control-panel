<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ui;

use App\Http\Controllers\Web\EmailVerificationController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

readonly class UiVerificationController
{
    public function __construct(
        private EmailVerificationController $webEmail
    ) {
    }

    public function index(Request $request, Response $response): Response
    {
        return $this->webEmail->index(
            $request->withAttribute('template', 'pages/verify_email.twig'),
            $response
        );
    }

    public function verify(Request $request, Response $response): Response
    {
        $res = $this->webEmail->verify(
            $request->withAttribute('template', 'pages/verify_email.twig'),
            $response
        );

        if ($res->getStatusCode() === 302) {
            $location = $res->getHeaderLine('Location');
            // Redirects to /login on success
            if (str_starts_with($location, '/login')) {
                 return $res->withHeader('Location', str_replace('/login', '/ui/login', $location));
            }
        }

        return $res;
    }

    public function resend(Request $request, Response $response): Response
    {
        $res = $this->webEmail->resend(
            $request->withAttribute('template', 'pages/verify_email.twig'),
            $response
        );

        if ($res->getStatusCode() === 302) {
            $location = $res->getHeaderLine('Location');
            // Redirects to /verify-email on success
            if (str_starts_with($location, '/verify-email')) {
                 return $res->withHeader('Location', str_replace('/verify-email', '/ui/verify-email', $location));
            }
        }

        return $res;
    }
}
