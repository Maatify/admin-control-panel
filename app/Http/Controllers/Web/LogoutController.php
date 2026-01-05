<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Contracts\AdminSessionRepositoryInterface;
use App\Domain\Service\RememberMeService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

readonly class LogoutController
{
    public function __construct(
        private AdminSessionRepositoryInterface $sessionRepository,
        private RememberMeService $rememberMeService
    ) {
    }

    public function logout(Request $request, Response $response): Response
    {
        // 1. Revoke Session
        $cookies = $request->getCookieParams();
        if (isset($cookies['auth_token'])) {
             $this->sessionRepository->invalidateSession($cookies['auth_token']);
        }

        // 2. Revoke Remember-Me (Updated)
        if (isset($cookies['remember_me'])) {
            $this->rememberMeService->revoke($cookies['remember_me']);
        }

        // 3. Clear Cookies
        return $response
            ->withHeader('Location', '/login')
            ->withStatus(302)
            ->withAddedHeader('Set-Cookie', 'auth_token=; Path=/; Max-Age=0; HttpOnly; SameSite=Strict')
            ->withAddedHeader('Set-Cookie', 'remember_me=; Path=/; Max-Age=0; HttpOnly; SameSite=Strict');
    }
}
