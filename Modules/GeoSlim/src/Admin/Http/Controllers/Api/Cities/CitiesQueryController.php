<?php

declare(strict_types=1);

namespace Maatify\GeoSlim\Admin\Http\Controllers\Api\Cities;

use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\AdminKernel\Infrastructure\Query\ListFilterResolver;
use Maatify\Geo\Service\GeoQueryService;
use Maatify\GeoSlim\Admin\Domain\List\CityListCapabilities;
use Maatify\Validation\Guard\ValidationGuard;
use Maatify\Validation\Schemas\SharedListQuerySchema;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class CitiesQueryController
{
    public function __construct(
        private GeoQueryService $queryService,
        private ValidationGuard $validationGuard,
        private ListFilterResolver $filterResolver,
        private JsonResponseFactory $json
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        /** @var array<string, mixed> $body */
        $body = (array) $request->getParsedBody();

        // 1) Validate request shape
        $this->validationGuard->check(new SharedListQuerySchema(), $body);

        /** @var array{
         *   page?: int,
         *   per_page?: int,
         *   search?: array{
         *     global?: string,
         *     columns?: array<string, string>
         *   },
         *   date?: array{
         *     from?: string,
         *     to?: string
         *   }
         * } $validated
         */
        $validated = $body;

        // 2) Build canonical ListQueryDTO
        $query = ListQueryDTO::fromArray($validated);

        // 3) Capabilities
        $capabilities = CityListCapabilities::define();

        // 4) Resolve filters
        $filters = $this->filterResolver->resolve($query, $capabilities);

        // 5) Execute service
        $result = $this->queryService->paginateCities(
            page:          $query->page,
            perPage:       $query->perPage,
            globalSearch:  $filters->globalSearch,
            columnFilters: $filters->columnFilters,
        );

        // 6) Return JSON
        return $this->json->data($response, $result);
    }
}

