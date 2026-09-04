<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Web;

use Maatify\AdminKernel\Application\Auth\AdminLogoutService;
use Maatify\AdminKernel\Application\Auth\DTO\AdminLogoutRequestDTO;
use Maatify\AdminKernel\Context\AdminContext;
use Maatify\AdminKernel\Context\RequestContext;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

readonly class LogoutController
{
    public function __construct(
        private AdminLogoutService $logoutService,
    ) {
    }

    public function logout(Request $request, Response $response): Response
    {
        $adminContext = $request->getAttribute(AdminContext::class);
        if (!$adminContext instanceof AdminContext) {
            throw new \RuntimeException('AdminContext missing');
        }

        $requestContext = $request->getAttribute(RequestContext::class);
        if (!$requestContext instanceof RequestContext) {
            throw new \RuntimeException('Request context missing');
        }

        $cookies = $request->getCookieParams();

        $result = $this->logoutService->logout(
            new AdminLogoutRequestDTO(
                adminId: $adminContext->adminId,
                authToken: $cookies['auth_token'] ?? null,
                rememberMeCookie: $cookies['remember_me'] ?? null,
                requestContext: $requestContext,
            )
        );

        $isSecure   = $request->getUri()->getScheme() === 'https';
        $secureFlag = $isSecure ? 'Secure;' : '';

        if ($result->clearAuthCookie) {
            $response = $response->withAddedHeader(
                'Set-Cookie',
                trim(
                    sprintf(
                        'auth_token=; Path=/; HttpOnly; SameSite=Strict; Max-Age=0; %s',
                        $secureFlag
                    ),
                    '; '
                )
            );
        }

        if ($result->clearRememberMeCookie) {
            $response = $response->withAddedHeader(
                'Set-Cookie',
                trim(
                    sprintf(
                        'remember_me=; Path=/; HttpOnly; SameSite=Strict; Max-Age=0; %s',
                        $secureFlag
                    ),
                    '; '
                )
            );
        }

        return $response
            ->withHeader('Location', '/login')
            ->withStatus(302);
    }
}
