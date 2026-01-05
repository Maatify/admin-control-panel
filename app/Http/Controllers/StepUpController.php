<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Service\StepUpService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class StepUpController
{
    public function __construct(
        private StepUpService $stepUpService
    ) {
    }

    public function verify(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        if (!is_array($data)) {
            $data = [];
        }
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

        $adminId = $request->getAttribute('admin_id');
        if (!is_int($adminId)) {
             $payload = json_encode(['error' => 'Authentication required']);
             $response->getBody()->write((string)$payload);
             return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $sessionId = $this->getSessionIdFromRequest($request);
        if ($sessionId === null) {
             $payload = json_encode(['error' => 'Session required']);
             $response->getBody()->write((string)$payload);
             return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $result = $this->stepUpService->verifyTotp($adminId, $sessionId, (string)$code, $requestedScope);

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
        $header = $request->getHeaderLine('Authorization');
        if (preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return $matches[1];
        }
        return null;
    }
}
