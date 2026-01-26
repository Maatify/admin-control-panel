<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Context\AdminContext;
use App\Domain\ActivityLog\Action\AdminActivityAction;
use App\Domain\ActivityLog\Service\AdminActivityLogService;
use App\Domain\Service\SessionRevocationService;
use App\Context\RequestContext;
use App\Domain\Service\AuthorizationService;
use App\Modules\Validation\Guard\ValidationGuard;
use App\Modules\Validation\Schemas\SessionRevokeSchema;
use App\Application\Services\DiagnosticsTelemetryService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use DomainException;
use App\Domain\Exception\IdentifierNotFoundException;

class SessionRevokeController
{
    public function __construct(
        private readonly SessionRevocationService $revocationService,
        private readonly AuthorizationService $authorizationService,
        private readonly ValidationGuard $validationGuard,
        private readonly DiagnosticsTelemetryService $telemetryService,
        private AdminActivityLogService $adminActivityLogService,

    ) {
    }

    /**
     * @param array<string, string> $args
     */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $adminContext = $request->getAttribute(\App\Context\AdminContext::class);
        if (!$adminContext instanceof \App\Context\AdminContext) {
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

            // ✅ Activity Log — admin manually revoked a session
            $this->adminActivityLogService->log(
                adminContext: $adminContext,
                requestContext: $requestContext,
                action: AdminActivityAction::SESSION_REVOKE,
                entityType: 'admin',
                entityId: $targetAdminId,
                metadata: [
                    'target_session_id_prefix' => substr($targetSessionHash, 0, 8) . '...',
                ]
            );

            // ✅ Telemetry — successful admin-initiated revoke
            try {
                $this->telemetryService->recordEvent(
                    eventKey: 'resource_mutation',
                    severity: 'INFO',
                    actorType: 'ADMIN',
                    actorId: $adminId,
                    metadata: [
                        'action'            => 'session_revoke',
                        'target_session_id' => $targetSessionHash,
                        'request_id' => $context->requestId,
                        'ip_address' => $context->ipAddress,
                        'user_agent' => $context->userAgent,
                        'route_name' => $context->routeName,
                    ]
                );
            } catch (\Throwable) {
                // swallow — telemetry must never affect request flow
            }

            $response->getBody()->write(json_encode(['status' => 'ok'], JSON_THROW_ON_ERROR));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');

        } catch (DomainException $e) {

            // ⚠️ Telemetry — failed revoke attempt (business rule)
            try {
                $this->telemetryService->recordEvent(
                    eventKey: 'resource_mutation',
                    severity: 'WARNING',
                    actorType: 'ADMIN',
                    actorId: $adminId,
                    metadata: [
                        'action'            => 'session_revoke_failed',
                        'reason'            => 'domain_exception',
                        'target_session_id' => $targetSessionHash,
                        'request_id' => $context->requestId,
                        'ip_address' => $context->ipAddress,
                        'user_agent' => $context->userAgent,
                        'route_name' => $context->routeName,
                    ]
                );
            } catch (\Throwable) {
                // swallow
            }

            $response->getBody()->write(
                json_encode(['error' => $e->getMessage()], JSON_THROW_ON_ERROR)
            );
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');

        } catch (IdentifierNotFoundException $e) {

            // ⚠️ Telemetry — target not found
            try {
                $this->telemetryService->recordEvent(
                    eventKey: 'resource_mutation',
                    severity: 'WARNING',
                    actorType: 'ADMIN',
                    actorId: $adminId,
                    metadata: [
                        'action'            => 'session_revoke_failed',
                        'reason'            => 'session_not_found',
                        'target_session_id' => $targetSessionHash,
                        'request_id' => $context->requestId,
                        'ip_address' => $context->ipAddress,
                        'user_agent' => $context->userAgent,
                        'route_name' => $context->routeName,
                    ]
                );
            } catch (\Throwable) {
                // swallow
            }

            $response->getBody()->write(
                json_encode(['error' => $e->getMessage()], JSON_THROW_ON_ERROR)
            );
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
    }
}
