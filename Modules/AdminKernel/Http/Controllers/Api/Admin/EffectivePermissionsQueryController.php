<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\Admin;

use Maatify\AdminKernel\Domain\Contracts\Permissions\EffectivePermissionsRepositoryInterface;
use Maatify\AdminKernel\Domain\List\EffectivePermissionsCapabilities;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\AdminKernel\Infrastructure\Query\ListFilterResolver;
use Maatify\Validation\Guard\ValidationGuard;
use Maatify\Validation\Schemas\SharedListQuerySchema;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class EffectivePermissionsQueryController
{
    public function __construct(
        private EffectivePermissionsRepositoryInterface $reader,
        private ValidationGuard $validation,
        private ListFilterResolver $filters
    ) {
    }

    /**
     * =========================================
     * Admin Effective Permissions — QUERY
     * POST /admins/{id}/permissions/effective
     * =========================================
     *
     * - Read-only
     * - Snapshot after full RBAC resolution
     * - Role + Direct permissions
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

        // 1) validate canonical list query
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

        // 2) build query dto
        $query = ListQueryDTO::fromArray($validated);

        // 3) capabilities (Effective Permissions)
        $capabilities = EffectivePermissionsCapabilities::define();

        // 4) resolve filters
        $resolved = $this->filters->resolve($query, $capabilities);

        // 5) execute reader
        $result = $this->reader->queryEffectivePermissionsForAdmin(
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
