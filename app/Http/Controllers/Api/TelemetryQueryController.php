<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-XX
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Context\RequestContext;
use App\Domain\ActivityLog\Action\AdminActivityAction;
use App\Domain\ActivityLog\Service\AdminActivityLogService;
use App\Domain\List\ListCapabilities;
use App\Domain\List\ListQueryDTO;
use App\Domain\Service\AuthorizationService;
use App\Domain\Telemetry\Contracts\TelemetryListReaderInterface;
use App\Infrastructure\Query\ListFilterResolver;
use App\Modules\Validation\Guard\ValidationGuard;
use App\Modules\Validation\Schemas\SharedListQuerySchema;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class TelemetryQueryController
{
    public function __construct(
        private TelemetryListReaderInterface $reader,
        private AuthorizationService $authorizationService,
        private ValidationGuard $validationGuard,
        private ListFilterResolver $filterResolver,
        private AdminActivityLogService $adminActivityLogService,
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        // ─────────────────────────────
        // Admin Context (MANDATORY)
        // ─────────────────────────────
        $adminContext = $request->getAttribute(\App\Context\AdminContext::class);
        if (!$adminContext instanceof \App\Context\AdminContext) {
            throw new \RuntimeException('AdminContext missing');
        }

        $adminId = $adminContext->adminId;

        /** @var array<string,mixed> $body */
        $body = (array) $request->getParsedBody();

        // ─────────────────────────────
        // 1️⃣ Validate canonical list/query request
        // ─────────────────────────────
        $this->validationGuard->check(new SharedListQuerySchema(), $body);

        // ─────────────────────────────
        // 2️⃣ Build canonical DTO
        // ─────────────────────────────
        /** @var array{
         *   page?: int,
         *   per_page?: int,
         *   search?: array{global?: string, columns?: array<string, string>},
         *   date?: array{from?: string, to?: string}
         * } $canonicalInput
         */
        $canonicalInput = $body;
        $query = ListQueryDTO::fromArray($canonicalInput);

        // ─────────────────────────────
        // 3️⃣ Authorization (HARD RULE)
        // Telemetry is INTERNAL / SENSITIVE
        // ─────────────────────────────
        $this->authorizationService->hasPermission(
            $adminId,
            'telemetry.view'
        );

        // ─────────────────────────────
        // 4️⃣ Declare LIST capabilities (ALIASES ONLY)
        // ─────────────────────────────
        $capabilities = new ListCapabilities(
            supportsGlobalSearch: true,
            searchableColumns: [
                'event_key',
                'route_name',
                'request_id',
            ],

            supportsColumnFilters: true,
            filterableColumns: [
                'event_key'  => 'event_key',
                'route_name' => 'route_name',
                'request_id' => 'request_id',
                'actor_type' => 'actor_type',
                'actor_id'   => 'actor_id',
                'ip_address' => 'ip_address',
            ],

            supportsDateFilter: true,
            dateColumn: 'occurred_at'
        );

        // ─────────────────────────────
        // 5️⃣ Resolve allowed filters only
        // (AND logic, exact match, canonical)
        // ─────────────────────────────
        $resolvedFilters = $this->filterResolver->resolve(
            $query,
            $capabilities
        );

        // ─────────────────────────────
        // 6️⃣ Execute reader (READ-ONLY)
        // ─────────────────────────────
        $result = $this->reader->getTelemetry(
            query  : $query,
            filters: $resolvedFilters
        );

        // ─────────────────────────────
        // 7️⃣ Activity Log (AUTHORITATIVE)
        // ─────────────────────────────

        $requestContext = $request->getAttribute(RequestContext::class);
        if (! $requestContext instanceof RequestContext) {
            throw new \RuntimeException('Request Context not present');
        }

        // 🔹 Activity Log (SUCCESS)
        $this->adminActivityLogService->log(
            adminContext: $adminContext,
            requestContext: $requestContext,
            action: AdminActivityAction::TELEMETRY_LIST,
            metadata: [
                'result_count' => count($result->data),
            ]
        );

        // ─────────────────────────────
        // Response
        // ─────────────────────────────
        $response->getBody()->write(
            json_encode($result, JSON_THROW_ON_ERROR)
        );

        return $response->withHeader(
            'Content-Type',
            'application/json'
        );
    }
}
