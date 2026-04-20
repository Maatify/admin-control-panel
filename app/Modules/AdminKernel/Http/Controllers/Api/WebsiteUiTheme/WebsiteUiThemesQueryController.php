<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\WebsiteUiTheme;

use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\AdminKernel\Domain\WebsiteUiTheme\List\WebsiteUiThemeListCapabilities;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\AdminKernel\Infrastructure\Query\ListFilterResolver;
use Maatify\Validation\Guard\ValidationGuard;
use Maatify\Validation\Schemas\SharedListQuerySchema;
use Maatify\WebsiteUiTheme\Service\WebsiteUiThemeQueryService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class WebsiteUiThemesQueryController
{
    public function __construct(
        private WebsiteUiThemeQueryService $queryService,
        private ValidationGuard $validationGuard,
        private ListFilterResolver $filterResolver,
        private JsonResponseFactory $json,
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        /** @var array<string, mixed> $body */
        $body = (array) $request->getParsedBody();

        $this->validationGuard->check(new SharedListQuerySchema(), $body);

        /** @var array{page?:int,per_page?:int,search?:array{global?:string,columns?:array<string,string>},date?:array{from?:string,to?:string}} $validated */
        $validated = $body;

        $query = ListQueryDTO::fromArray($validated);
        $capabilities = WebsiteUiThemeListCapabilities::define();
        $filters = $this->filterResolver->resolve($query, $capabilities);

        $result = $this->queryService->paginate(
            page: $query->page,
            perPage: $query->perPage,
            globalSearch: $filters->globalSearch,
            columnFilters: $filters->columnFilters,
        );

        return $this->json->data($response, $result);
    }
}
