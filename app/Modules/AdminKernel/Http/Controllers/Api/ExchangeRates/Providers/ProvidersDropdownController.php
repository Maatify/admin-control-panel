<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\ExchangeRates\Providers;

use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\ExchangeRates\Admin\Provider\Service\ProviderQueryService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class ProvidersDropdownController
{
    public function __construct(
        private ProviderQueryService $queryService,
        private JsonResponseFactory $json
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        // Safe reuse of list method for active dropdown mapping
        $result = $this->queryService->list(1, 1000, null, ['is_active' => 1]);

        return $this->json->data($response, ['data' => $result['data']]);
    }
}
