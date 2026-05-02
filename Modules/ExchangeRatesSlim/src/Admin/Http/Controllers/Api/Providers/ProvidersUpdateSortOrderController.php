<?php

declare(strict_types=1);

namespace Maatify\ExchangeRatesSlim\Admin\Http\Controllers\Api\Providers;

use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\ExchangeRates\Admin\Provider\Service\ProviderCommandService;
use Maatify\ExchangeRatesSlim\Admin\Domain\Validation\ProviderUpdateSortOrderSchema;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class ProvidersUpdateSortOrderController
{
    public function __construct(
        private ProviderCommandService $commandService,
        private ValidationGuard $validationGuard,
        private JsonResponseFactory $json
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $this->validationGuard->check(new ProviderUpdateSortOrderSchema(), $body);

        /** @var int $id */
        $id = $body['id'];
        /** @var int $displayOrder */
        $displayOrder = $body['display_order'];

        $this->commandService->updateDisplayOrder($id, $displayOrder);

        return $this->json->success($response);
    }
}
