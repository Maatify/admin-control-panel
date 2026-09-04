<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\Sessions;

use DomainException;
use Maatify\AdminKernel\Context\RequestContext;
use Maatify\AdminKernel\Domain\Exception\IdentifierNotFoundException;
use Maatify\AdminKernel\Domain\Service\AuthorizationService;
use Maatify\AdminKernel\Domain\Service\SessionRevocationService;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\AdminKernel\Validation\Schemas\Session\SessionRevokeSchema;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class SessionRevokeController
{
    public function __construct(
        private readonly SessionRevocationService $revocationService,
        private readonly AuthorizationService $authorizationService,
        private readonly ValidationGuard $validationGuard,
        private readonly JsonResponseFactory $json,
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
            throw new HttpUnauthorizedException($request, 'Current session not found');
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

            return $this->json->data($response, ['status' => 'ok']);

        } catch (DomainException $e) {
            throw new HttpBadRequestException($request, $e->getMessage());
        } catch (IdentifierNotFoundException $e) {
            throw new HttpNotFoundException($request, $e->getMessage());
        }
    }
}
