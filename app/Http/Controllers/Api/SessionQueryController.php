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

final readonly class SessionQueryController
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

        /** @var array<string,mixed> $body */
        $body = (array) $request->getParsedBody();

        // 1️⃣ Validate canonical list/query request
        $this->validationGuard->check(new SharedListQuerySchema(), $body);

        // 2️⃣ Build canonical DTO
        $query = ListQueryDTO::fromArray($body);

        // 3️⃣ Authorization scope (HARD RULE)
        $adminIdFilter = $this->authorizationService->hasPermission($adminId, 'sessions.view_all')
            ? null
            : $adminId;

        // 4️⃣ Current session hash
        $cookies = $request->getCookieParams();
        $token = isset($cookies['auth_token']) ? (string) $cookies['auth_token'] : '';
        $currentSessionHash = $token !== '' ? hash('sha256', $token) : '';

        // 5️⃣ Declare LIST capabilities (ALIASES ONLY)
        $capabilities = new ListCapabilities(
            supportsGlobalSearch: true,
            searchableColumns: [
                'session_id',
                'admin_id',
            ],

            supportsColumnFilters: true,
            filterableColumns: [
                'session_id' => 'session_id',
                'status'     => 'status',
                'admin_id'   => 'admin_id',
            ],

            supportsDateFilter: true,
            dateColumn: 'created_at'
        );

        // 6️⃣ Resolve allowed filters only
        $resolvedFilters = $this->filterResolver->resolve($query, $capabilities);

        // 7️⃣ Execute reader (Reader enforces SQL + scope)
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
