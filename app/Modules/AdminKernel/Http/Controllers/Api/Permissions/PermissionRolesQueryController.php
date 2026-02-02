<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\Permissions;

use Maatify\AdminKernel\Domain\Contracts\Permissions\PermissionRolesQueryRepositoryInterface;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\AdminKernel\Domain\List\PermissionRolesQueryCapabilities;
use Maatify\AdminKernel\Infrastructure\Query\ListFilterResolver;
use Maatify\Validation\Guard\ValidationGuard;
use Maatify\Validation\Schemas\SharedListQuerySchema;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class PermissionRolesQueryController
{
    public function __construct(
        private PermissionRolesQueryRepositoryInterface $repository,
        private ValidationGuard $validation,
        private ListFilterResolver $filters
    ) {}

    /**
     * POST /api/permissions/{permissionId}/roles/query
     *
     * - Read-only
     * - Paginated
     * - UI-driven
     *
     * @param array<string,string> $args
     */
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {

        $permissionId = (int) $args['permission_id'];

        /** @var array<string,mixed> $body */
        $body = (array) $request->getParsedBody();

        // ─────────────────────────────
        // Canonical list validation
        // ─────────────────────────────
        $this->validation->check(new SharedListQuerySchema(), $body);

        /** @var array{
         *   page?: int,
         *   per_page?: int,
         *   search?: array{
         *     global?: string,
         *     columns?: array<string,string>
         *   }
         * } $validated
         */
        $validated = $body;

        $query = ListQueryDTO::fromArray($validated);

        // ─────────────────────────────
        // Capabilities (LOCKED)
        // ─────────────────────────────
        $capabilities = PermissionRolesQueryCapabilities::define();

        $resolvedFilters = $this->filters->resolve($query, $capabilities);

        // ─────────────────────────────
        // Query
        // ─────────────────────────────
        $result = $this->repository->queryRolesForPermission(
            $permissionId,
            $query,
            $resolvedFilters
        );

        $response->getBody()->write(
            json_encode($result, JSON_THROW_ON_ERROR)
        );

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}
