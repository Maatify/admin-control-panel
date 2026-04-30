<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\ExchangeRates\Rates;

use Maatify\AdminKernel\Domain\ExchangeRates\Validation\RateUpdateSortOrderSchema;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\ExchangeRates\Admin\Rate\Service\RateCommandService;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class RatesUpdateSortOrderController
{
    public function __construct(
        private RateCommandService $commandService,
        private ValidationGuard $validationGuard,
        private JsonResponseFactory $json
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $this->validationGuard->check(new RateUpdateSortOrderSchema(), $body);

        /** @var int $id */
        $id = $body['id'];
        /** @var int $displayOrder */
        $displayOrder = $body['display_order'];

        $this->commandService->updateDisplayOrder($id, $displayOrder);

        return $this->json->success($response);
    }
}
