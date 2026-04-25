<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\Category;

use Maatify\AdminKernel\Domain\Category\List\CategoryListCapabilities;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\AdminKernel\Infrastructure\Query\ListFilterResolver;
use Maatify\Category\Service\CategoryQueryService;
use Maatify\Validation\Guard\ValidationGuard;
use Maatify\Validation\Schemas\SharedListQuerySchema;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class CategoriesQueryController
{
    public function __construct(
        private CategoryQueryService $queryService,
        private ValidationGuard      $validationGuard,
        private ListFilterResolver   $filterResolver,
        private JsonResponseFactory  $json,
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        /** @var array<string, mixed> $body */
        $body = (array) $request->getParsedBody();

        $this->validationGuard->check(new SharedListQuerySchema(), $body);

        /** @var array{page?: int, per_page?: int, search?: array{global?: string, columns?: array<string, string>}} $body */
        $query = ListQueryDTO::fromArray($body);

        $filters = $this->filterResolver->resolve($query, CategoryListCapabilities::define());

        $result = $this->queryService->paginate(
            page:          $query->page,
            perPage:       $query->perPage,
            globalSearch:  $filters->globalSearch,
            columnFilters: $filters->columnFilters,
        );

        return $this->json->data($response, $result);
    }
}



