<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\ExchangeRates\Providers;

use Maatify\AdminKernel\Domain\ExchangeRates\Validation\ProviderSetActiveSchema;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\ExchangeRates\Admin\Provider\Service\ProviderCommandService;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class ProvidersSetActiveController
{
    public function __construct(
        private ProviderCommandService $commandService,
        private ValidationGuard $validationGuard,
        private JsonResponseFactory $json
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $this->validationGuard->check(new ProviderSetActiveSchema(), $body);

        /** @var int $id */
        $id = $body['id'];
        /** @var bool $isActive */
        $isActive = $body['is_active'];

        $this->commandService->updateStatus($id, $isActive);

        return $this->json->success($response);
    }
}
