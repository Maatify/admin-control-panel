<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api;

use Maatify\AdminKernel\Domain\Service\SessionRevocationService;
use Maatify\AdminKernel\Context\RequestContext;
use Maatify\AdminKernel\Domain\Service\AuthorizationService;
use App\Modules\Validation\Guard\ValidationGuard;
use App\Modules\Validation\Schemas\SessionRevokeSchema;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use DomainException;
use Maatify\AdminKernel\Domain\Exception\IdentifierNotFoundException;

class SessionRevokeController
{
    public function __construct(
        private readonly SessionRevocationService $revocationService,
        private readonly AuthorizationService $authorizationService,
        private readonly ValidationGuard $validationGuard,

    ) {
    }

    /**
     * @param array<string, string> $args
     */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $adminContext = $request->getAttribute(\Maatify\AdminKernel\Context\AdminContext::class);
        if (!$adminContext instanceof \Maatify\AdminKernel\Context\AdminContext) {
            throw new \RuntimeException("AdminContext missing");
        }
        $adminId = $adminContext->adminId;

        $context = $request->getAttribute(RequestContext::class);
        if (!$context instanceof RequestContext) {
            throw new \RuntimeException("Request context missing");
        }

        $this->authorizationService->checkPermission($adminId, 'sessions.revoke', $context);

        $this->validationGuard->check(new SessionRevokeSchema(), $args);

        $targetSessionHash = $args['session_id'];

        // Fetch Current Session Hash
        $cookies = $request->getCookieParams();
        $token = isset($cookies['auth_token']) ? (string)$cookies['auth_token'] : '';
        $currentSessionHash = $token !== '' ? hash('sha256', $token) : '';

        if ($currentSessionHash === '') {
            $response->getBody()->write(
                json_encode(['error' => 'Current session not found'], JSON_THROW_ON_ERROR)
            );
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        try {
            $targetAdminId = $this->revocationService->revokeByHash(
                $targetSessionHash,
                $currentSessionHash,
                $context
            );

            $requestContext = $request->getAttribute(RequestContext::class);
            if (! $requestContext instanceof RequestContext) {
                throw new \RuntimeException('Request Context not present');
            }

            $response->getBody()->write(json_encode(['status' => 'ok'], JSON_THROW_ON_ERROR));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');

        } catch (DomainException $e) {

            $response->getBody()->write(
                json_encode(['error' => $e->getMessage()], JSON_THROW_ON_ERROR)
            );
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');

        } catch (IdentifierNotFoundException $e) {

            $response->getBody()->write(
                json_encode(['error' => $e->getMessage()], JSON_THROW_ON_ERROR)
            );
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
    }
}
