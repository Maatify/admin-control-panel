<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\Currency;

use Maatify\AdminKernel\Domain\Currency\Validation\CurrencyUpdateSortOrderSchema;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\Currency\Service\CurrencyCommandService;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class CurrenciesUpdateSortOrderController
{
    public function __construct(
        private CurrencyCommandService $commandService,
        private ValidationGuard $validationGuard,
        private JsonResponseFactory $json
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        /** @var array<string, mixed> $body */
        $body = (array) $request->getParsedBody();

        // 1) Validate request
        $this->validationGuard->check(new CurrencyUpdateSortOrderSchema(), $body);

        $id = $body['id'];
        $displayOrder = $body['display_order'];

        if (!is_int($id) || !is_int($displayOrder)) {
            throw new \RuntimeException('Invalid validated payload.');
        }

        // 3) Execute service
        $this->commandService->reorder($id, $displayOrder);

        // 4) Return success using JSON response factory
        return $this->json->success($response);
    }
}
