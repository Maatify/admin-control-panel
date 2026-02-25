<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Middleware;

use Maatify\AdminKernel\Context\RequestContext;
use Maatify\AdminKernel\Domain\Service\SessionValidationService;
use Maatify\AdminKernel\Domain\Service\RememberMeService;
use Maatify\AdminKernel\Http\Auth\AuthSurface;
use Maatify\AdminKernel\Domain\Exception\InvalidCredentialsException;
use Maatify\AdminKernel\Domain\Exception\InvalidSessionException;
use Maatify\AdminKernel\Domain\Exception\ExpiredSessionException;
use Maatify\AdminKernel\Domain\Exception\RevokedSessionException;
use Maatify\AdminKernel\Http\Cookie\CookieFactoryService;
use Maatify\AdminKernel\Domain\Security\RedirectToken\RedirectTokenServiceInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;


// Phase 13.7 LOCK: Auth surface detection MUST use AuthSurface::isApi()
/**
 * Guard Middleware for Session Validation.
 *
 * PHASE 13.7: Auth Boundary Lock & Regression Guard
 * - STRICT Web vs API detection (path-based).
 * - Explicit failure responses (401 JSON vs 302 Redirect).
 * - Canonical exception handling.
 */
readonly class SessionGuardMiddleware implements MiddlewareInterface
{
    public function __construct(
        private SessionValidationService $sessionValidationService,
        private RememberMeService $rememberMeService,
        private CookieFactoryService $cookieFactory,
        private RedirectTokenServiceInterface $redirectTokenService
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $isApi = AuthSurface::isApi($request);

        $cookies = $request->getCookieParams();
        $token   = $cookies['auth_token'] ?? null;

        $context = $request->getAttribute(RequestContext::class);
        if (!$context instanceof RequestContext) {
            throw new \RuntimeException("Request context missing");
        }

        if ($token === null) {
            if (isset($cookies['remember_me'])) {
                return $this->attemptRememberFallback(
                    $request,
                    $handler,
                    $context,
                    $cookies['remember_me'],
                    $isApi
                );
            }

            return $this->handleFailure($isApi, 'No session token provided.', $request);
        }

        try {
            return $this->proceedWithValidSession($token, $context, $request, $handler);
        }
        catch (InvalidSessionException | ExpiredSessionException | RevokedSessionException $e) {

            if (!isset($cookies['remember_me'])) {
                return $this->handleFailure($isApi, $e->getMessage(), $request);
            }

            return $this->attemptRememberFallback(
                $request,
                $handler,
                $context,
                $cookies['remember_me'],
                $isApi
            );
        }
    }

    /**
     * Proceed with a validated session.
     */
    private function proceedWithValidSession(
        string $token,
        RequestContext $context,
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {

        $adminId = $this->sessionValidationService->validate($token, $context);

        $sessionHash = hash('sha256', $token);

        $request = $request
            ->withAttribute('admin_id', $adminId)
            ->withAttribute('session_hash', $sessionHash);

        return $handler->handle($request);
    }

    /**
     * Attempt Remember-Me fallback after session failure.
     *
     * STRICT: No silent fallback.
     * STRICT: Re-validation required.
     */
    private function attemptRememberFallback(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
        RequestContext $context,
        string $rememberToken,
        bool $isApi
    ): ResponseInterface {

        try {
            $result = $this->rememberMeService->processAutoLogin(
                $rememberToken,
                $context
            );

            $sessionToken     = $result['session_token'];
            $newRememberToken = $result['remember_me_token'];

            // Inject new token into request
            $cookies = $request->getCookieParams();
            $cookies['auth_token'] = $sessionToken;
            $request = $request->withCookieParams($cookies);

            // Re-validate session using new token (STRICT)
            $adminId = $this->sessionValidationService->validate($sessionToken, $context);

            $sessionHash = hash('sha256', $sessionToken);

            $request = $request
                ->withAttribute('admin_id', $adminId)
                ->withAttribute('session_hash', $sessionHash);

            $response = $handler->handle($request);

            return $this->attachRememberCookies(
                $response,
                $request,
                $sessionToken,
                $newRememberToken
            );

        } catch (InvalidCredentialsException) {

            return $this->handleRememberFailure($request, $isApi);
        }
    }

    /**
     * Attach rotated session + remember cookies.
     */
    private function attachRememberCookies(
        ResponseInterface $response,
        ServerRequestInterface $request,
        string $sessionToken,
        string $rememberToken
    ): ResponseInterface {

        $isSecure = $request->getUri()->getScheme() === 'https';

        $sessionCookie = $this->cookieFactory->createSessionCookie(
            $sessionToken,
            $isSecure
        );

        $rememberMeCookie = $this->cookieFactory->createRememberMeCookie(
            $rememberToken,
            $isSecure,
            60 * 60 * 24 * 30
        );

        return $response
            ->withAddedHeader('Set-Cookie', $sessionCookie)
            ->withAddedHeader('Set-Cookie', $rememberMeCookie);
    }

    /**
     * Handle remember-me failure.
     */
    private function handleRememberFailure(
        ServerRequestInterface $request,
        bool $isApi
    ): ResponseInterface {

        $isSecure = $request->getUri()->getScheme() === 'https';
        $clearCookie = $this->cookieFactory->clearRememberMeCookie($isSecure);

        if ($isApi) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode(['error' => 'Invalid session'], JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Set-Cookie', $clearCookie)
                ->withStatus(401)
                ->withHeader('Content-Type', 'application/json');
        }

        $path = $request->getUri()->getPath();
        $token = $this->redirectTokenService->create($path);

        return (new \Slim\Psr7\Response())
            ->withHeader('Set-Cookie', $clearCookie)
            ->withHeader('Location', '/login?r=' . $token)
            ->withStatus(302);
    }

    /**
     * Canonical failure handler.
     */
    private function handleFailure(bool $isApi, string $message, ServerRequestInterface $request): ResponseInterface
    {
        if ($isApi) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode(['error' => $message], JSON_THROW_ON_ERROR));
            return $response
                ->withStatus(401)
                ->withHeader('Content-Type', 'application/json');
        }

        $path = $request->getUri()->getPath();
        $token = $this->redirectTokenService->create($path);

        return (new \Slim\Psr7\Response())
            ->withHeader('Location', '/login?r=' . $token)
            ->withStatus(302);
    }
}
