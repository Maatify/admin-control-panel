<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\Currency;

use Maatify\AdminKernel\Domain\Currency\Validation\CurrencySetActiveSchema;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\Currency\Command\UpdateCurrencyStatusCommand;
use Maatify\Currency\Service\CurrencyCommandService;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class CurrenciesSetActiveController
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
        $this->validationGuard->check(new CurrencySetActiveSchema(), $body);

        $id = $body['id'];
        $isActive = $body['is_active'];

        if (!is_int($id) || !is_bool($isActive)) {
            throw new \RuntimeException('Invalid validated payload.');
        }

        // 3) Execute service
        $dto = $this->commandService->updateStatus(new UpdateCurrencyStatusCommand(
            id: $id,
            isActive: $isActive
        ));

        // 4) Return success using JSON response factory
        return $this->json->success($response);
    }
}
