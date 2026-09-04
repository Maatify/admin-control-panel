<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\Admin;

use Maatify\AdminKernel\Domain\Contracts\Permissions\DirectPermissionsRepositoryInterface;
use Maatify\AdminKernel\Domain\List\DirectPermissionsCapabilities;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\AdminKernel\Infrastructure\Query\ListFilterResolver;
use Maatify\Validation\Guard\ValidationGuard;
use Maatify\Validation\Schemas\SharedListQuerySchema;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class DirectPermissionsQueryController
{
    public function __construct(
        private DirectPermissionsRepositoryInterface $reader,
        private ValidationGuard $validation,
        private ListFilterResolver $filters
    ) {
    }

    /**
     * =========================================
     * Admin Direct Permissions — QUERY
     * POST /admins/{admin_id}/permissions/direct/query
     * =========================================
     *
     * - Read-only
     * - Direct permissions only (overrides)
     * - allow / deny
     * - expires_at supported
     * - Canonical list pipeline
     *
     * @param array<string,string> $args
     */
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $adminId = (int) $args['admin_id'];

        /** @var array<string,mixed> $body */
        $body = (array) $request->getParsedBody();

        // 1) Validate canonical list query
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

        // 2) Build query DTO
        $query = ListQueryDTO::fromArray($validated);

        // 3) Capabilities (Direct Permissions)
        $capabilities = DirectPermissionsCapabilities::define();

        // 4) Resolve filters
        $resolved = $this->filters->resolve($query, $capabilities);

        // 5) Execute repository
        $result = $this->reader->queryDirectPermissionsForAdmin(
            $adminId,
            $query,
            $resolved
        );

        $response->getBody()->write(
            json_encode($result, JSON_THROW_ON_ERROR)
        );

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}
