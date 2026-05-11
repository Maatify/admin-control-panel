<?php

declare(strict_types=1);

namespace Maatify\SettingsSlim\Admin\Http\Controllers\Api;

use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\AdminKernel\Infrastructure\Query\ListFilterResolver;
use Maatify\Settings\Admin\Setting\Service\AdminSettingService;
use Maatify\SettingsSlim\Admin\Domain\List\SettingListCapabilities;
use Maatify\Validation\Guard\ValidationGuard;
use Maatify\Validation\Schemas\SharedListQuerySchema;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class SettingsListController
{
    public function __construct(
        private AdminSettingService $service,
        private ValidationGuard $validationGuard,
        private ListFilterResolver $filterResolver,
        private JsonResponseFactory $json
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        /** @var array<string, mixed> $body */
        $body = (array) $request->getParsedBody();

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
         *   }
         * } $validated
         */
        $validated = $body;

        $query = ListQueryDTO::fromArray($validated);
        $capabilities = SettingListCapabilities::define();
        $filters = $this->filterResolver->resolve($query, $capabilities);

        $result = $this->service->list(
            page: $query->page,
            perPage: $query->perPage,
            globalSearch: $filters->globalSearch,
            columnFilters: $filters->columnFilters
        );

        return $this->json->data($response, $result);
    }
}
