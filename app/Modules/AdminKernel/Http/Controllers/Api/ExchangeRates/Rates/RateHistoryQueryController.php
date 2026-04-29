<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\ExchangeRates\Rates;

use Maatify\AdminKernel\Domain\ExchangeRates\Validation\RateHistoryQuerySchema;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\ExchangeRates\Admin\RateHistory\Service\RateHistoryQueryService;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class RateHistoryQueryController
{
    public function __construct(
        private RateHistoryQueryService $queryService,
        private ValidationGuard $validationGuard,
        private JsonResponseFactory $json
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $this->validationGuard->check(new RateHistoryQuerySchema(), $body);

        /** @var int $rateId */
        $rateId = $body['rate_id'];
        $page = isset($body['page']) && is_int($body['page']) ? $body['page'] : 1;
        $perPage = isset($body['per_page']) && is_int($body['per_page']) ? $body['per_page'] : 20;

        $result = $this->queryService->listByRateId($rateId, $page, $perPage);

        return $this->json->data($response, $result);
    }
}
