<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Ui\Auth;

use Maatify\AdminKernel\Http\Controllers\Web\TwoFactorController;
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
        // ADDITIVE START
        $query = $request->getQueryParams();

        if (isset($query['scope']) && is_string($query['scope'])) {
            $request = $request->withAttribute('scope', $query['scope']);
        }

        if (isset($query['r']) && is_string($query['r'])) {
            $request = $request->withAttribute('r', $query['r']);
        }
        // ADDITIVE END

        return $this->web2fa->verify(
            $request->withAttribute('template', 'pages/2fa_verify.twig'),
            $response
        );
    }

    public function doVerify(Request $request, Response $response): Response
    {
        // ADDITIVE START
        $data = $request->getParsedBody();

        if (is_array($data)) {
            if (isset($data['scope']) && is_string($data['scope'])) {
                $request = $request->withAttribute('scope', $data['scope']);
            }

            if (isset($data['r']) && is_string($data['r'])) {
                $request = $request->withAttribute('r', $data['r']);
            }
        }
        // ADDITIVE END

        return $this->web2fa->doVerify(
            $request->withAttribute('template', 'pages/2fa_verify.twig'),
            $response
        );
    }
}
