<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Service\SessionValidationService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SessionGuardMiddleware implements MiddlewareInterface
{
    private SessionValidationService $sessionValidationService;

    public function __construct(SessionValidationService $sessionValidationService)
    {
        $this->sessionValidationService = $sessionValidationService;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Check for Bearer Token
        $authHeader = $request->getHeaderLine('Authorization');
        $token = null;

        if (!empty($authHeader) && str_starts_with($authHeader, 'Bearer ')) {
            $token = substr($authHeader, 7);
        }

        // Check for Cookie if no Bearer Token
        if ($token === null) {
            $cookies = $request->getCookieParams();
            if (isset($cookies['auth_token'])) {
                $token = $cookies['auth_token'];
            }
        }

        if ($token === null) {
            return $this->handleFailure($request, 'No session token provided.');
        }

        try {
            $adminId = $this->sessionValidationService->validate($token);
            $request = $request->withAttribute('admin_id', $adminId);
            return $handler->handle($request);
        } catch (\App\Domain\Exception\InvalidSessionException | \App\Domain\Exception\ExpiredSessionException | \App\Domain\Exception\RevokedSessionException $e) {
            return $this->handleFailure($request, $e->getMessage());
        }
    }

    private function handleFailure(ServerRequestInterface $request, string $message): ResponseInterface
    {
        // Detect if Web request based on Accept header
        $acceptHeader = $request->getHeaderLine('Accept');
        $isWeb = str_contains($acceptHeader, 'text/html');

        if ($isWeb) {
            $response = new \Slim\Psr7\Response();
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        // API request: Throw exception
        throw new \App\Domain\Exception\InvalidSessionException($message);
    }
}
