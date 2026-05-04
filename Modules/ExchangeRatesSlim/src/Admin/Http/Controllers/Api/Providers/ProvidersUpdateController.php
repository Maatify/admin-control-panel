<?php

declare(strict_types=1);

namespace Maatify\ExchangeRatesSlim\Admin\Http\Controllers\Api\Providers;

use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\ExchangeRates\Admin\Provider\Command\UpdateProviderCommand;
use Maatify\ExchangeRates\Admin\Provider\Service\ProviderCommandService;
use Maatify\ExchangeRatesSlim\Admin\Domain\Validation\ProviderUpdateSchema;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class ProvidersUpdateController
{
    public function __construct(
        private ProviderCommandService $commandService,
        private ValidationGuard $validationGuard,
        private JsonResponseFactory $json
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $this->validationGuard->check(new ProviderUpdateSchema(), $body);

        /** @var int $id */
        $id = $body['id'];
        /** @var string $name */
        $name = $body['name'];
        /** @var string|null $description */
        $description = $body['description'] ?? null;

        $this->commandService->update(new UpdateProviderCommand(
            id: $id,
            name: $name,
            description: $description
        ));

        return $this->json->success($response);
    }
}
