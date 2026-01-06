<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ui;

use App\Http\Controllers\Ui\Shared\UiResponseNormalizer;
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
        $res = $this->webEmail->index(
            $request->withAttribute('template', 'pages/verify_email.twig'),
            $response
        );
        return UiResponseNormalizer::normalize($res);
    }

    public function verify(Request $request, Response $response): Response
    {
        $res = $this->webEmail->verify(
            $request->withAttribute('template', 'pages/verify_email.twig'),
            $response
        );
        return UiResponseNormalizer::normalize($res);
    }

    public function resend(Request $request, Response $response): Response
    {
        $res = $this->webEmail->resend(
            $request->withAttribute('template', 'pages/verify_email.twig'),
            $response
        );
        return UiResponseNormalizer::normalize($res);
    }
}
