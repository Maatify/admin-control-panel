<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\ExchangeRates\Providers;

use Maatify\AdminKernel\Domain\ExchangeRates\Validation\ProviderCreateSchema;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\ExchangeRates\Admin\Provider\Command\CreateProviderCommand;
use Maatify\ExchangeRates\Admin\Provider\Service\ProviderCommandService;
use Maatify\ExchangeRates\Exception\ExchangeRatesCodeAlreadyExistsException;
use Maatify\Exceptions\Exception\Conflict\GenericConflictMaatifyException;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class ProvidersCreateController
{
    public function __construct(
        private ProviderCommandService $commandService,
        private ValidationGuard $validationGuard,
        private JsonResponseFactory $json
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $this->validationGuard->check(new ProviderCreateSchema(), $body);

        /** @var string $name */
        $name = $body['name'];
        /** @var string $code */
        $code = $body['code'];
        /** @var string|null $description */
        $description = $body['description'] ?? null;

        try {
            $this->commandService->create(new CreateProviderCommand(
                name: $name,
                code: $code,
                description: $description
            ));
        } catch (ExchangeRatesCodeAlreadyExistsException $e) {
            throw new GenericConflictMaatifyException($e->getMessage());
        }

        return $this->json->success($response);
    }
}
