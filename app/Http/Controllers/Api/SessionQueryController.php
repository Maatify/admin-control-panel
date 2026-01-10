<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\List\ListCapabilities;
use App\Domain\List\ListQueryDTO;
use App\Domain\Session\Reader\SessionListReaderInterface;
use App\Domain\Service\AuthorizationService;
use App\Infrastructure\Query\ListFilterResolver;
use App\Modules\Validation\Guard\ValidationGuard;
use App\Modules\Validation\Schemas\SharedListQuerySchema;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

readonly class SessionQueryController
{
    public function __construct(
        private SessionListReaderInterface $reader,
        private AuthorizationService $authorizationService,
        private ValidationGuard $validationGuard,
        private ListFilterResolver $filterResolver
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $adminId = $request->getAttribute('admin_id');
        assert(is_int($adminId));

        $body = (array)$request->getParsedBody();

        // ✅ Validation (MANDATORY)
        $this->validationGuard->check(new SharedListQuerySchema(), $body);

        // Build canonical ListQueryDTO
        $query = ListQueryDTO::fromArray($body);

        // Permission-based admin filter
        if ($this->authorizationService->hasPermission($adminId, 'sessions.view_all')) {
            $adminIdFilter = isset($body['filters']['admin_id']) && $body['filters']['admin_id'] !== ''
                ? (int)$body['filters']['admin_id']
                : null;
        } else {
            $adminIdFilter = $adminId;
        }

        // Current session hash
        $cookies = $request->getCookieParams();
        $token = isset($cookies['auth_token']) ? (string)$cookies['auth_token'] : '';
        $currentSessionHash = $token !== '' ? hash('sha256', $token) : '';

        // Declare LIST capabilities (Sessions = time-based)
        $capabilities = new ListCapabilities(
            supportsGlobalSearch: true,
            searchableColumns: [
                's.session_id',
            ],

            supportsColumnFilters: true,
            filterableColumns: [
                'session_id' => 's.session_id',
                'status'     => 's.is_revoked',
                'admin_id'   => 's.admin_id',
            ],

            supportsDateFilter: true,
            dateColumn: 's.created_at'
        );

        $resolvedFilters = $this->filterResolver->resolve($query, $capabilities);

        $result = $this->reader->getSessions(
            query: $query,
            filters: $resolvedFilters,
            adminIdFilter: $adminIdFilter,
            currentSessionHash: $currentSessionHash
        );

        $response->getBody()->write(json_encode($result, JSON_THROW_ON_ERROR));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
