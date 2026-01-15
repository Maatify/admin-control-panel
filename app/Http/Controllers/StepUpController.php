<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Context\RequestContext;
use App\Domain\Service\StepUpService;
use App\Modules\Validation\Guard\ValidationGuard;
use App\Modules\Validation\Schemas\StepUpVerifySchema;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class StepUpController
{
    public function __construct(
        private StepUpService $stepUpService,
        private ValidationGuard $validationGuard
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
            $requestedScope = \App\Domain\Enum\Scope::tryFrom((string)$scopeStr);
            if ($requestedScope === null) {
                $response->getBody()->write((string)json_encode(['error' => 'Invalid scope']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
        }

        $adminContext = $request->getAttribute(\App\Context\AdminContext::class);
        if (!$adminContext instanceof \App\Context\AdminContext) {
             $payload = json_encode(['error' => 'Authentication required']);
             $response->getBody()->write((string)$payload);
             return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        $adminId = $adminContext->adminId;

        $sessionId = $this->getSessionIdFromRequest($request);
        if ($sessionId === null) {
             $payload = json_encode(['error' => 'Session required']);
             $response->getBody()->write((string)$payload);
             return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $context = $request->getAttribute(RequestContext::class);
        if (!$context instanceof RequestContext) {
             throw new \RuntimeException("Request context missing");
        }

        $result = $this->stepUpService->verifyTotp($adminId, $sessionId, (string)$code, $context, $requestedScope);

        if ($result->success) {
            $response->getBody()->write((string)json_encode(['status' => 'granted', 'scope' => $requestedScope?->value ?? 'login']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        $response->getBody()->write((string)json_encode(['error' => $result->errorReason]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
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
