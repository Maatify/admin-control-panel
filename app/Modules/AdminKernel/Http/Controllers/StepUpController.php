<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers;

use Maatify\AdminKernel\Application\Services\DiagnosticsTelemetryService;
use Maatify\AdminKernel\Context\RequestContext;
use Maatify\AdminKernel\Domain\Exception\AdminKernelValidationException;
use Maatify\AdminKernel\Domain\Exception\InvalidSessionException;
use Maatify\AdminKernel\Domain\Exception\PermissionDeniedException;
use Maatify\AdminKernel\Domain\Service\StepUpService;
use Maatify\AdminKernel\Validation\Schemas\Auth\StepUpVerifySchema;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

readonly class StepUpController
{
    public function __construct(
        private StepUpService $stepUpService,
        private ValidationGuard $validationGuard,
        private DiagnosticsTelemetryService $telemetryService
    ) {
    }

    public function verify(Request $request, Response $response): Response
    {
        $data = (array)$request->getParsedBody();

        $this->validationGuard->check(new StepUpVerifySchema(), $data);

        $code = isset($data['code']) ? (string)$data['code'] : '';
        $scopeStr = isset($data['scope']) ? (string)$data['scope'] : null;

        $requestedScope = null;
        if ($scopeStr !== null) {
            $requestedScope = \Maatify\AdminKernel\Domain\Enum\Scope::tryFrom((string)$scopeStr);
            if ($requestedScope === null) {
                throw new AdminKernelValidationException('Invalid scope');
            }
        }

        $adminContext = $request->getAttribute(\Maatify\AdminKernel\Context\AdminContext::class);
        if (!$adminContext instanceof \Maatify\AdminKernel\Context\AdminContext) {
             throw new InvalidSessionException('Authentication required');
        }
        $adminId = $adminContext->adminId;

        $sessionId = $this->getSessionIdFromRequest($request);
        if ($sessionId === null) {
             throw new InvalidSessionException('Session required');
        }

        $context = $request->getAttribute(RequestContext::class);
        if (!$context instanceof RequestContext) {
             throw new \RuntimeException("Request context missing");
        }

        $result = $this->stepUpService->verifyTotp($adminId, $sessionId, (string)$code, $context, $requestedScope);


        // ✅ Telemetry (best-effort, never breaks auth flow)
        try {
            $metadata = [
                'scope' => $requestedScope?->value ?? 'login',
                // never store raw token/session id
                'session_hash' => hash('sha256', $sessionId),
                'status' => $result->success ? 'granted' : 'denied',
                'request_id' => $context->requestId,
                'ip_address' => $context->ipAddress,
                'user_agent' => $context->userAgent,
                'route_name' => $context->routeName,
            ];

            if (!$result->success) {
                $metadata['error_reason'] = (string)$result->errorReason;
            }

            $this->telemetryService->recordEvent(
                eventKey: $result->success ? 'auth_stepup_success' : 'auth_stepup_failure',
                severity: $result->success ? 'INFO' : 'WARNING',
                actorType: 'ADMIN',
                actorId: $adminId,
                metadata: $metadata
            );
        } catch (\Throwable) {
            // swallow
        }

        if ($result->success) {
            $response->getBody()->write((string)json_encode(['status' => 'granted', 'scope' => $requestedScope?->value ?? 'login']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        throw new PermissionDeniedException((string)$result->errorReason);
    }

    private function getSessionIdFromRequest(Request $request): ?string
    {
        // Must match extraction logic used elsewhere
        $cookies = $request->getCookieParams();
        if (isset($cookies['auth_token'])) {
            return (string)$cookies['auth_token'];
        }
        return null;
    }
}
