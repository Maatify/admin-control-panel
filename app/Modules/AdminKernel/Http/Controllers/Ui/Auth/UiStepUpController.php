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

        if (isset($query['return_to']) && is_string($query['return_to'])) {
            $request = $request->withAttribute('return_to', $query['return_to']);
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

            if (isset($data['return_to']) && is_string($data['return_to'])) {
                $request = $request->withAttribute('return_to', $data['return_to']);
            }
        }
        // ADDITIVE END

        return $this->web2fa->doVerify(
            $request->withAttribute('template', 'pages/2fa_verify.twig'),
            $response
        );
    }
}
