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
        private RememberMeService $rememberMeService,
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

            // Revoke Remember-Me tokens for this admin
            // Note: Ideally we should only revoke the current one if possible, but
            // "Revoke remember-me token" implies the current session's persistence.
            // Since we don't have the remember_me token explicitly in the session (it's a separate cookie),
            // and we might want to allow "logout everywhere" vs "logout this device".
            // The prompt says: "Logout fully revokes persistent access".
            // It also says: "Revoke remember-me token via RememberMeService".
            // Does it mean ALL tokens or just the one on this device?
            // If I logout, I expect to be logged out.
            // But if I have another session on another device, do I kill that too?
            // The prompt "Logout fully revokes persistent access" is slightly ambiguous.
            // However, `RememberMeService::revoke(int $adminId)` deletes ALL tokens for the admin.
            // `RememberMeService::revokeBySelector(string $selector)` deletes specific.
            // The cookie has the selector.

            if (isset($cookies['remember_me'])) {
                $parts = explode(':', (string)$cookies['remember_me']);
                if (count($parts) === 2) {
                    $this->rememberMeService->revokeBySelector($parts[0]);
                }
            } else {
                 // Fallback: If we don't have the cookie but we are logging out, maybe we should kill all?
                 // Safe default for "secure logout" often implies killing session.
                 // But let's stick to revoking the current one if present.
                 // If the user wants to "logout everywhere", that's usually a specific action.
                 // But `revoke(int $adminId)` was defined in the service as `deleteByAdminId`.
                 // Let's check my service implementation.
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

        $rememberMeClear = sprintf(
            "remember_me=; Path=/; HttpOnly; SameSite=Strict; Max-Age=0; %s",
            $secureFlag
        );

        return $response
            ->withAddedHeader('Set-Cookie', $cookieHeader)
            ->withAddedHeader('Set-Cookie', $rememberMeClear)
            ->withHeader('Location', '/login')
            ->withStatus(302);
    }
}
