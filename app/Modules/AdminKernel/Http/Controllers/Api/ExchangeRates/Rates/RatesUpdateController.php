<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\ExchangeRates\Rates;

use Maatify\AdminKernel\Domain\ExchangeRates\Validation\RateUpdateSchema;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\ExchangeRates\Admin\Rate\Command\UpdateRateCommand;
use Maatify\ExchangeRates\Admin\Rate\Service\RateCommandService;
use Maatify\ExchangeRates\Exception\ExchangeRatesConflictException;
use Maatify\Exceptions\Exception\Conflict\GenericConflictMaatifyException;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class RatesUpdateController
{
    public function __construct(
        private RateCommandService $commandService,
        private ValidationGuard $validationGuard,
        private JsonResponseFactory $json
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $this->validationGuard->check(new RateUpdateSchema(), $body);

        /** @var int $id */
        $id = $body['id'];
        /** @var string $rate */
        $rate = $body['rate'];
        /** @var string|null $recordedAt */
        $recordedAt = $body['recorded_at'] ?? null;

        try {
            $this->commandService->updateRate(new UpdateRateCommand(
                id: $id,
                rate: $rate,
                recordedAt: $recordedAt
            ));
        } catch (ExchangeRatesConflictException $e) {
            throw new GenericConflictMaatifyException($e->getMessage());
        }

        return $this->json->success($response);
    }
}
