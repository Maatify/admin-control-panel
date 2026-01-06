<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ui;

use App\Http\Controllers\Ui\Shared\UiResponseNormalizer;
use App\Http\Controllers\Web\TwoFactorController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

readonly class UiStepUpController
{
    public function __construct(
        private TwoFactorController $web2fa
    ) {
    }

    public function verify(Request $request, Response $response): Response
    {
        return $this->web2fa->verify(
            $request->withAttribute('template', 'pages/2fa_verify.twig'),
            $response
        );
    }

    public function doVerify(Request $request, Response $response): Response
    {
        return $this->web2fa->doVerify(
            $request->withAttribute('template', 'pages/2fa_verify.twig'),
            $response
        );
    }
}
