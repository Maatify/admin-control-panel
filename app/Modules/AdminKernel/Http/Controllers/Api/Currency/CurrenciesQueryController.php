<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\Currency;

use Maatify\AdminKernel\Domain\Currency\List\CurrencyListCapabilities;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\AdminKernel\Infrastructure\Query\ListFilterResolver;
use Maatify\Currency\Service\CurrencyQueryService;
use Maatify\Validation\Guard\ValidationGuard;
use Maatify\Validation\Schemas\SharedListQuerySchema;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class CurrenciesQueryController
{
    public function __construct(
        private CurrencyQueryService $queryService,
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

        /**
         * @var array{
         *   page?: int,
         *   per_page?: int,
         *   search?: array{
         *     global?: string,
         *     columns?: array<string, string>
         *   },
         *   date?: array{
         *     from?: string,
         *     to?: string
         *   },
         *   language_id?: int
         * } $validated
         */
        $validated = $body;

        // 2) Build canonical ListQueryDTO
        $query = ListQueryDTO::fromArray($validated);

        // 3) Capabilities
        $capabilities = CurrencyListCapabilities::define();

        // 4) Resolve filters
        $filters = $this->filterResolver->resolve($query, $capabilities);

        $languageId = null;
        if (isset($validated['language_id'])) {
            $languageId = $validated['language_id'];
        }

        // 5) Execute service
        $result = $this->queryService->paginate(
            page: $query->page,
            perPage: $query->perPage,
            globalSearch: $filters->globalSearch,
            columnFilters: $filters->columnFilters,
            languageId: $languageId
        );

        // Map DTOs to array
        $result['data'] = array_map(static fn($dto) => $dto->toArray(), $result['data']);

        /** @var array<string, mixed> $responsePayload */
        $responsePayload = $result;

        // 6) Return JSON
        return $this->json->data($response, $responsePayload);
    }
}
