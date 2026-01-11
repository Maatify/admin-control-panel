<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-11 22:17
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\List\ListCapabilities;
use App\Domain\List\ListQueryDTO;
use App\Domain\ActivityLog\Reader\ActivityLogListReaderInterface;
use App\Domain\Service\AuthorizationService;
use App\Infrastructure\Query\ListFilterResolver;
use App\Modules\Validation\Guard\ValidationGuard;
use App\Modules\Validation\Schemas\SharedListQuerySchema;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class ActivityLogQueryController
{
    public function __construct(
        private ActivityLogListReaderInterface $reader,
        private AuthorizationService $authorizationService,
        private ValidationGuard $validationGuard,
        private ListFilterResolver $filterResolver
    )
    {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $adminId = $request->getAttribute('admin_id');
        assert(is_int($adminId));

        /** @var array<string,mixed> $body */
        $body = (array)$request->getParsedBody();

        // 1️⃣ Validate canonical list/query request
        $this->validationGuard->check(new SharedListQuerySchema(), $body);

        // 2️⃣ Build canonical DTO
        /** @var array{
         *   page?: int,
         *   per_page?: int,
         *   search?: array{global?: string, columns?: array<string, string>},
         *   date?: array{from?: string, to?: string}
         * } $canonicalInput
         */
        $canonicalInput = $body;
        $query = ListQueryDTO::fromArray($canonicalInput);

        // 3️⃣ Authorization scope (HARD RULE)
        // Activity logs are SECURITY-SENSITIVE
        $this->authorizationService->hasPermission($adminId, 'activity_logs.view');

        // 4️⃣ Declare LIST capabilities (ALIASES ONLY)
        $capabilities = new ListCapabilities(
            supportsGlobalSearch : true,
            searchableColumns    : [
                'action',
                'request_id',
            ],

            supportsColumnFilters: true,
            filterableColumns    : [
                'actor_type'  => 'actor_type',
                'actor_id'    => 'actor_id',
                'action'      => 'action',
                'entity_type' => 'entity_type',
                'entity_id'   => 'entity_id',
                'request_id'  => 'request_id',
            ],

            supportsDateFilter   : true,
            dateColumn           : 'occurred_at'
        );

        // 5️⃣ Resolve allowed filters only
        $resolvedFilters = $this->filterResolver->resolve($query, $capabilities);

        // 6️⃣ Execute reader
        $result = $this->reader->getActivityLogs(
            query  : $query,
            filters: $resolvedFilters
        );

        $response->getBody()->write(json_encode($result, JSON_THROW_ON_ERROR));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
