<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\ActivityLog\Action\AdminActivityAction;
use App\Domain\ActivityLog\Service\AdminActivityLogService;
use App\Domain\Service\SessionRevocationService;
use App\Context\RequestContext;
use App\Domain\Service\AuthorizationService;
use App\Modules\Validation\Guard\ValidationGuard;
use App\Modules\Validation\Schemas\SessionBulkRevokeSchema;
use App\Application\Telemetry\HttpTelemetryRecorderFactory;
use App\Modules\Telemetry\Enum\TelemetryEventTypeEnum;
use App\Modules\Telemetry\Enum\TelemetrySeverityEnum;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use DomainException;

readonly class SessionBulkRevokeController
{
    public function __construct(
        private SessionRevocationService $revocationService,
        private AuthorizationService $authorizationService,
        private ValidationGuard $validationGuard,
        private HttpTelemetryRecorderFactory $telemetryFactory,
        private AdminActivityLogService $adminActivityLogService,
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
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

        $body = (array)$request->getParsedBody();
        $this->validationGuard->check(new SessionBulkRevokeSchema(), $body);

        /** @var string[] $hashes */
        $hashes = $body['session_ids'];

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
            $this->revocationService->revokeBulk(
                $hashes,
                $currentSessionHash,
                $context
            );

            // ✅ Activity Log — admin manually revoked multiple sessions
            $this->adminActivityLogService->log(
                adminContext: $adminContext,
                requestContext: $context,
                action: AdminActivityAction::SESSION_BULK_REVOKE,
                entityType: 'admin',
                entityId: null, // bulk operation — no single target
                metadata: [
                    'sessions_count' => count($hashes),
                ]
            );

            // ✅ Telemetry — successful bulk revoke
            try {
                $this->telemetryFactory
                    ->admin($context)
                    ->record(
                        $adminId,
                        TelemetryEventTypeEnum::RESOURCE_MUTATION,
                        TelemetrySeverityEnum::INFO,
                        [
                            'action'        => 'session_revoke_bulk',
                            'sessions_count'=> count($hashes),
                        ]
                    );
            } catch (\Throwable) {
                // swallow — telemetry must never affect request flow
            }

            $response->getBody()->write(json_encode(['status' => 'ok'], JSON_THROW_ON_ERROR));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');

        } catch (DomainException $e) {

            // ⚠️ Telemetry — bulk revoke failed (domain rule)
            try {
                $this->telemetryFactory
                    ->admin($context)
                    ->record(
                        $adminId,
                        TelemetryEventTypeEnum::RESOURCE_MUTATION,
                        TelemetrySeverityEnum::WARN,
                        [
                            'action'        => 'session_revoke_bulk_failed',
                            'sessions_count'=> count($hashes),
                            'reason'        => $e->getMessage(),
                        ]
                    );
            } catch (\Throwable) {
                // swallow
            }

            $response->getBody()->write(
                json_encode(['error' => $e->getMessage()], JSON_THROW_ON_ERROR)
            );
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }
}
