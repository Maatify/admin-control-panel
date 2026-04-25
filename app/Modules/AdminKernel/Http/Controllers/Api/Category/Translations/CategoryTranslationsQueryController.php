<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\Category\Translations;

use Maatify\AdminKernel\Domain\Category\List\CategoryTranslationListCapabilities;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\AdminKernel\Infrastructure\Query\ListFilterResolver;
use Maatify\Category\Service\CategoryQueryService;
use Maatify\Validation\Guard\ValidationGuard;
use Maatify\Validation\Schemas\SharedListQuerySchema;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class CategoryTranslationsQueryController
{
    public function __construct(
        private CategoryQueryService $queryService,
        private ValidationGuard $validationGuard,
        private ListFilterResolver $filterResolver,
        private JsonResponseFactory $json
    ) {}

    /** @param array<string, string> $args */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $categoryId = (int) $args['category_id'];

        /** @var array<string, mixed> $body */
        $body = (array) $request->getParsedBody();

        $this->validationGuard->check(new SharedListQuerySchema(), $body);

        /** @var array<string, mixed> $validated */
        $validated = $body;

        $query = ListQueryDTO::fromArray($validated);

        $capabilities = CategoryTranslationListCapabilities::define();

        $filters = $this->filterResolver->resolve($query, $capabilities);

        $list = $this->queryService->listTranslationsPaginated(
            categoryId:    $categoryId,
            page:          $query->page,
            perPage:       $query->perPage,
            globalSearch:  $filters->globalSearch,
            columnFilters: $filters->columnFilters,
        );

        return $this->json->data($response, $list);
    }
}

