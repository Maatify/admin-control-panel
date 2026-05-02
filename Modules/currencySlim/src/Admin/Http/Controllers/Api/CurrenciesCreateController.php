<?php

declare(strict_types=1);

namespace Maatify\currencySlim\Admin\Http\Controllers\Api;

use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\Currency\Command\CreateCurrencyCommand;
use Maatify\Currency\Service\CurrencyCommandService;
use Maatify\currencySlim\Admin\Domain\Validation\CurrencyCreateSchema;
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

        // 3) Execute service
        $dto = $this->commandService->create(new CreateCurrencyCommand(
            code: $code,
            name: $name,
            symbol: $symbol,
            isActive: $isActive,
        ));

        // 4) Return success using JSON response factory
        return $this->json->success($response);
    }
}
