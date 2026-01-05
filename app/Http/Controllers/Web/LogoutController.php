<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Contracts\AdminSessionRepositoryInterface;
use App\Domain\Contracts\ClientInfoProviderInterface;
use App\Domain\Contracts\SecurityEventLoggerInterface;
use App\Domain\DTO\SecurityEventDTO;
use DateTimeImmutable;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

readonly class LogoutController
{
    public function __construct(
        private AdminSessionRepositoryInterface $sessionRepository,
        private SecurityEventLoggerInterface $securityEventLogger,
        private ClientInfoProviderInterface $clientInfoProvider
    ) {
    }

    public function logout(Request $request, Response $response): Response
    {
        $adminId = $request->getAttribute('admin_id');

        // Check for session token in cookies
        $cookies = $request->getCookieParams();
        $token = isset($cookies['auth_token']) ? (string)$cookies['auth_token'] : null;

        // Perform logout logic only if we have an identified admin
        // Note: Middleware ensures admin_id is present for protected routes.
        if (is_int($adminId)) {
            // Log the logout event
            $this->securityEventLogger->log(new SecurityEventDTO(
                $adminId,
                'admin_logout',
                ['severity' => 'INFO'],
                $this->clientInfoProvider->getIpAddress(),
                $this->clientInfoProvider->getUserAgent(),
                new DateTimeImmutable()
            ));

            // Invalidate the session in the repository
            if ($token !== null) {
                $this->sessionRepository->invalidateSession($token);
            }
        }

        // Always clear the cookie (Idempotency)
        $isSecure = $request->getUri()->getScheme() === 'https';
        $secureFlag = $isSecure ? 'Secure;' : '';

        // Max-Age=0 to expire immediately
        $cookieHeader = sprintf(
            "auth_token=; Path=/; HttpOnly; SameSite=Strict; Max-Age=0; %s",
            $secureFlag
        );
        $cookieHeader = trim($cookieHeader, '; ');

        return $response
            ->withHeader('Set-Cookie', $cookieHeader)
            ->withHeader('Location', '/login')
            ->withStatus(302);
    }
}
