<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\DTO\Session\SessionListQueryDTO;
use App\Domain\Session\Reader\SessionListReaderInterface;
use App\Domain\Service\AuthorizationService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class SessionQueryController
{
    public function __construct(
        private SessionListReaderInterface $reader,
        private AuthorizationService $authorizationService
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $adminId = $request->getAttribute('admin_id');
        assert(is_int($adminId));

        $body = $request->getParsedBody();
        if (!is_array($body)) {
            $body = [];
        }

        $page = isset($body['page']) ? (int)$body['page'] : 1;
        $perPage = isset($body['per_page']) ? (int)$body['per_page'] : 20;
        $filters = isset($body['filters']) && is_array($body['filters']) ? $body['filters'] : [];

        // Permission-based Admin Filter Logic
        if ($this->authorizationService->hasPermission($adminId, 'sessions.view_all')) {
            // Allowed to filter by any admin
            $adminIdFilter = isset($filters['admin_id']) && $filters['admin_id'] !== '' ? (int)$filters['admin_id'] : null;
        } else {
            // Restricted to self
            $adminIdFilter = $adminId;
        }

        // Fetch Current Session Hash
        $cookies = $request->getCookieParams();
        $token = isset($cookies['auth_token']) ? (string)$cookies['auth_token'] : '';
        $currentSessionHash = $token !== '' ? hash('sha256', $token) : '';

        $query = new SessionListQueryDTO(
            page: $page,
            per_page: $perPage,
            filters: $filters,
            current_session_id: $currentSessionHash,
            admin_id: $adminIdFilter
        );

        $result = $this->reader->getSessions($query);

        $response->getBody()->write(json_encode($result, JSON_THROW_ON_ERROR));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
