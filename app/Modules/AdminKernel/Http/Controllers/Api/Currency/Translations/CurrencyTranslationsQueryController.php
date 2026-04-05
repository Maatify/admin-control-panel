<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\Currency\Translations;

use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\Currency\Service\CurrencyQueryService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class CurrencyTranslationsQueryController
{
    public function __construct(
        private CurrencyQueryService $queryService,
        private JsonResponseFactory $json
    ) {
    }

    /**
     * @param array<string, string> $args
     */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $currencyId = (int) $args['currency_id'];

        // Translations query for currency does not have a paginated service method, only `listTranslations`
        $list = $this->queryService->listTranslationsPaginated($currencyId);

        return $this->json->data($response, $list);
    }
}
