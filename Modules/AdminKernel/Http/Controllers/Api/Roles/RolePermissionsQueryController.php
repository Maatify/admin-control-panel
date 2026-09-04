<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\Roles;

use Maatify\AdminKernel\Domain\Contracts\Roles\RolePermissionsRepositoryInterface;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\AdminKernel\Domain\List\RolePermissionsCapabilities;
use Maatify\AdminKernel\Infrastructure\Query\ListFilterResolver;
use Maatify\Validation\Guard\ValidationGuard;
use Maatify\Validation\Schemas\SharedListQuerySchema;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

readonly class RolePermissionsQueryController
{
    public function __construct(
        private RolePermissionsRepositoryInterface $repository,
        private ValidationGuard $validationGuard,
        private ListFilterResolver $filterResolver
    ) {
    }

    /**
     * @param array<string,string> $args
     */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        /** @var array<string,mixed> $body */
        $body = (array)$request->getParsedBody();

        // 1) Validate
        $this->validationGuard->check(new SharedListQuerySchema(), $body);

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

        $capabilities = RolePermissionsCapabilities::define();
        $filters = $this->filterResolver->resolve($query, $capabilities);

        $roleId = (int)$args['id'];

        $result = $this->repository->queryForRole($roleId, $query, $filters);

        $response->getBody()->write(
            json_encode($result, JSON_THROW_ON_ERROR)
        );

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}
