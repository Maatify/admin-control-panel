<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\Currency;

use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\Currency\Service\CurrencyQueryService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class CurrenciesDropdownController
{
    public function __construct(
        private CurrencyQueryService $queryService,
        private JsonResponseFactory $json
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        /** @var array<string, mixed> $body */
        $body = (array) $request->getParsedBody();

        $languageId = null;
        if (isset($body['language_id']) && is_int($body['language_id'])) {
            $languageId = $body['language_id'];
        }

        $list = $this->queryService->activeList($languageId);

        $data = array_map(static fn($dto) => $dto->toArray(), $list);

        /** @var array<string, mixed> $responsePayload */
        $responsePayload = $data;

        return $this->json->data($response, $responsePayload);
    }
}
