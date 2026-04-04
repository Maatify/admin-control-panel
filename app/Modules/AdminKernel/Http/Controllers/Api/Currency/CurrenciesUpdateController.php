<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\Currency;

use Maatify\AdminKernel\Domain\Currency\Validation\CurrencyUpdateSchema;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\Currency\Command\UpdateCurrencyCommand;
use Maatify\Currency\Service\CurrencyCommandService;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class CurrenciesUpdateController
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
        $this->validationGuard->check(new CurrencyUpdateSchema(), $body);

        $id = $body['id'];
        $code = $body['code'];
        $name = $body['name'];
        $symbol = $body['symbol'];
        $isActive = $body['is_active'];
        $displayOrder = $body['display_order'];

        if (!is_int($id) || !is_string($code) || !is_string($name) || !is_string($symbol) || !is_bool($isActive) || !is_int($displayOrder)) {
            throw new \RuntimeException('Invalid validated payload.');
        }

        // 3) Execute service
        $dto = $this->commandService->update(new UpdateCurrencyCommand(
            id: $id,
            code: $code,
            name: $name,
            symbol: $symbol,
            isActive: $isActive,
            displayOrder: $displayOrder
        ));

        // 4) Return success using JSON response factory
        return $this->json->success($response);
    }
}
