<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\Admin;

use Maatify\AdminKernel\Domain\Contracts\Permissions\DirectPermissionsAssignableRepositoryInterface;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\AdminKernel\Domain\List\DirectPermissionsAssignableCapabilities;
use Maatify\AdminKernel\Infrastructure\Query\ListFilterResolver;
use Maatify\Validation\Guard\ValidationGuard;
use Maatify\Validation\Schemas\SharedListQuerySchema;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class DirectPermissionsAssignableQueryController
{
    public function __construct(
        private DirectPermissionsAssignableRepositoryInterface $repository,
        private ValidationGuard $validation,
        private ListFilterResolver $filters
    ) {}

    /**
     * POST /admins/{adminId}/permissions/direct/assignable/query
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

        // canonical list validation
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

        $capabilities = DirectPermissionsAssignableCapabilities::define();

        $resolved = $this->filters->resolve($query, $capabilities);

        $result = $this->repository->queryAssignablePermissionsForAdmin(
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
