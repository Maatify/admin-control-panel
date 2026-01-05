<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Contracts\AdminSessionValidationRepositoryInterface;
use App\Domain\Contracts\ClientInfoProviderInterface;
use App\Domain\Contracts\SecurityEventLoggerInterface;
use App\Domain\DTO\SecurityEventDTO;
use App\Domain\Service\RememberMeService;
use DateTimeImmutable;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

readonly class LogoutController
{
    public function __construct(
        private AdminSessionValidationRepositoryInterface $sessionRepository,
        private SecurityEventLoggerInterface $securityEventLogger,
        private ClientInfoProviderInterface $clientInfoProvider,
        private RememberMeService $rememberMeService
    ) {
    }

    public function logout(Request $request, Response $response): Response
    {
        $adminId = $request->getAttribute('admin_id');

        // Check for session token in cookies
        $cookies = $request->getCookieParams();
        $token = isset($cookies['auth_token']) ? (string)$cookies['auth_token'] : null;

        // Perform logout logic only if we have an identified admin
        if (is_int($adminId)) {
            // Log the logout event
            $this->securityEventLogger->log(new SecurityEventDTO(
                $adminId,
                'admin_logout',
                'info',
                [], // Context
                $this->clientInfoProvider->getIpAddress(),
                $this->clientInfoProvider->getUserAgent(),
                new DateTimeImmutable()
            ));

            // Token + Identity Binding Check
            if ($token !== null) {
                $session = $this->sessionRepository->findSession($token);

                // Only invalidate if the session belongs to the current admin
                if ($session !== null && (int)$session['admin_id'] === $adminId) {
                    $this->sessionRepository->revokeSession($token);
                }
            }
        }

        // Revoke Remember-Me
        if (isset($cookies['remember_me'])) {
            $this->rememberMeService->revoke($cookies['remember_me']);
        }

        // Always clear the cookies (Idempotency)
        $isSecure = $request->getUri()->getScheme() === 'https';
        $secureFlag = $isSecure ? 'Secure;' : '';

        // Max-Age=0 to expire immediately
        $authCookie = sprintf(
            "auth_token=; Path=/; HttpOnly; SameSite=Strict; Max-Age=0; %s",
            $secureFlag
        );

        $rememberMeCookie = sprintf(
            "remember_me=; Path=/; HttpOnly; SameSite=Strict; Max-Age=0; %s",
            $secureFlag
        );

        return $response
            ->withAddedHeader('Set-Cookie', trim($authCookie, '; '))
            ->withAddedHeader('Set-Cookie', trim($rememberMeCookie, '; '))
            ->withHeader('Location', '/login')
            ->withStatus(302);
    }
}
