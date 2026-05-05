<?php

declare(strict_types=1);

namespace Maatify\GeoSlim\Admin\Http\Controllers\Api\Cities;

use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\Geo\Service\GeoQueryService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class CitiesDropdownController
{
    public function __construct(
        private GeoQueryService $queryService,
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

        $countryId = null;
        if (isset($body['country_id']) && is_int($body['country_id'])) {
            $countryId = $body['country_id'];
        }

        $list = $countryId !== null
            ? $this->queryService->activeCitiesByCountryId($countryId, $languageId)
            : [];

        return $this->json->data($response, ['data' => $list]);
    }
}

