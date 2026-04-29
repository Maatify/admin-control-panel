<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\ExchangeRates\Rates;

use Maatify\AdminKernel\Domain\ExchangeRates\List\RateListCapabilities;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\AdminKernel\Infrastructure\Query\ListFilterResolver;
use Maatify\ExchangeRates\Admin\Rate\Service\RateQueryService;
use Maatify\Validation\Guard\ValidationGuard;
use Maatify\Validation\Schemas\SharedListQuerySchema;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class RatesQueryController
{
    public function __construct(
        private RateQueryService $queryService,
        private ValidationGuard $validationGuard,
        private ListFilterResolver $filterResolver,
        private JsonResponseFactory $json
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $this->validationGuard->check(new SharedListQuerySchema(), $body);

        $query = ListQueryDTO::fromArray($body);
        $capabilities = RateListCapabilities::define();
        $filters = $this->filterResolver->resolve($query, $capabilities);

        $result = $this->queryService->list(
            page: $query->page,
            perPage: $query->perPage,
            globalSearch: $filters->globalSearch,
            columnFilters: $filters->columnFilters
        );

        return $this->json->data($response, $result);
    }
}
