<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Service\SessionRevocationService;
use App\Domain\Service\AuthorizationService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use DomainException;

class SessionBulkRevokeController
{
    public function __construct(
        private SessionRevocationService $revocationService,
        private AuthorizationService $authorizationService
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $adminId = $request->getAttribute('admin_id');
        assert(is_int($adminId));

        $this->authorizationService->checkPermission($adminId, 'sessions.revoke');

        $body = $request->getParsedBody();
        $hashes = $body['session_ids'] ?? [];

        if (!is_array($hashes)) {
            $response->getBody()->write(json_encode(['error' => 'Invalid payload'], JSON_THROW_ON_ERROR));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Validate content
        foreach ($hashes as $hash) {
            if (!is_string($hash)) {
                 $response->getBody()->write(json_encode(['error' => 'Invalid session ID format'], JSON_THROW_ON_ERROR));
                 return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }
        }

        // Fetch Current Session Hash
        $cookies = $request->getCookieParams();
        $token = isset($cookies['auth_token']) ? (string)$cookies['auth_token'] : '';
        $currentSessionHash = $token !== '' ? hash('sha256', $token) : '';

        if ($currentSessionHash === '') {
             $response->getBody()->write(json_encode(['error' => 'Current session not found'], JSON_THROW_ON_ERROR));
             return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        try {
            /** @var string[] $hashes */
            $this->revocationService->revokeBulk($hashes, $currentSessionHash);

            $response->getBody()->write(json_encode(['status' => 'ok'], JSON_THROW_ON_ERROR));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');

        } catch (DomainException $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()], JSON_THROW_ON_ERROR));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }
}
