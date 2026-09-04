<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\Permissions;

use Maatify\AdminKernel\Domain\Contracts\Permissions\PermissionAdminsQueryRepositoryInterface;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\AdminKernel\Domain\List\PermissionAdminsQueryCapabilities;
use Maatify\AdminKernel\Infrastructure\Query\ListFilterResolver;
use Maatify\Validation\Guard\ValidationGuard;
use Maatify\Validation\Schemas\SharedListQuerySchema;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class PermissionAdminsQueryController
{
    public function __construct(
        private PermissionAdminsQueryRepositoryInterface $repository,
        private ValidationGuard $validation,
        private ListFilterResolver $filters
    ) {}

    /**
     * POST /api/permissions/{permissionId}/admins/query
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
        // Capabilities
        // ─────────────────────────────
        $capabilities = PermissionAdminsQueryCapabilities::define();

        $resolvedFilters = $this->filters->resolve($query, $capabilities);

        // ─────────────────────────────
        // Query
        // ─────────────────────────────
        $result = $this->repository->queryAdminsForPermission(
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
