<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\ExchangeRates\Rates;

use Maatify\AdminKernel\Domain\ExchangeRates\Validation\RateCreateSchema;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\ExchangeRates\Admin\Rate\Command\CreateRateCommand;
use Maatify\ExchangeRates\Admin\Rate\Service\RateCommandService;
use Maatify\ExchangeRates\Exception\ExchangeRatesConflictException;
use Maatify\Exceptions\Exception\Conflict\GenericConflictMaatifyException;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class RatesCreateController
{
    public function __construct(
        private RateCommandService $commandService,
        private ValidationGuard $validationGuard,
        private JsonResponseFactory $json
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $this->validationGuard->check(new RateCreateSchema(), $body);

        /** @var int $providerId */
        $providerId = $body['provider_id'];
        /** @var string $baseCurrencyCode */
        $baseCurrencyCode = $body['base_currency_code'];
        /** @var string $targetCurrencyCode */
        $targetCurrencyCode = $body['target_currency_code'];
        /** @var string $rate */
        $rate = $body['rate'];
        /** @var string|null $recordedAt */
        $recordedAt = $body['recorded_at'] ?? null;

        try {
            $this->commandService->create(new CreateRateCommand(
                providerId: $providerId,
                baseCurrencyCode: $baseCurrencyCode,
                targetCurrencyCode: $targetCurrencyCode,
                rate: $rate,
                recordedAt: $recordedAt
            ));
        } catch (ExchangeRatesConflictException $e) {
            throw new GenericConflictMaatifyException($e->getMessage());
        }

        return $this->json->success($response);
    }
}
