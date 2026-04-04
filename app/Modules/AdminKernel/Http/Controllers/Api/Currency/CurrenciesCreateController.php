<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\Currency;

use Maatify\AdminKernel\Domain\Currency\Validation\CurrencyCreateSchema;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\Currency\Command\CreateCurrencyCommand;
use Maatify\Currency\Service\CurrencyCommandService;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class CurrenciesCreateController
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
        $this->validationGuard->check(new CurrencyCreateSchema(), $body);

        $code = $body['code'];
        $name = $body['name'];
        $symbol = $body['symbol'];

        if (!is_string($code) || !is_string($name) || !is_string($symbol)) {
            throw new \RuntimeException('Invalid validated payload.');
        }

        $isActive = true;
        if (array_key_exists('is_active', $body)) {
            if (!is_bool($body['is_active'])) {
                throw new \RuntimeException('Invalid is_active payload.');
            }
            $isActive = $body['is_active'];
        }

        $displayOrder = 0;
        if (array_key_exists('display_order', $body)) {
            if (!is_int($body['display_order'])) {
                throw new \RuntimeException('Invalid display_order payload.');
            }
            $displayOrder = $body['display_order'];
        }

        // 3) Execute service
        $dto = $this->commandService->create(new CreateCurrencyCommand(
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
