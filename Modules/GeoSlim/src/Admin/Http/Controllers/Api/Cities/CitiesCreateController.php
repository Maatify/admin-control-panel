<?php

declare(strict_types=1);

namespace Maatify\GeoSlim\Admin\Http\Controllers\Api\Cities;

use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\Geo\Command\CreateCityCommand;
use Maatify\Geo\Service\GeoCommandService;
use Maatify\GeoSlim\Admin\Domain\Validation\CityCreateSchema;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class CitiesCreateController
{
    public function __construct(
        private GeoCommandService $commandService,
        private ValidationGuard $validationGuard,
        private JsonResponseFactory $json
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        /** @var array<string, mixed> $body */
        $body = (array) $request->getParsedBody();

        // 1) Validate request
        $this->validationGuard->check(new CityCreateSchema(), $body);

        $countryId = $body['country_id'];
        $name      = $body['name'];

        if (!is_int($countryId) || !is_string($name)) {
            throw new \RuntimeException('Invalid validated payload.');
        }

        $code = null;
        if (array_key_exists('code', $body) && is_string($body['code'])) {
            $code = $body['code'];
        }

        $isActive = true;
        if (array_key_exists('is_active', $body)) {
            if (!is_bool($body['is_active'])) {
                throw new \RuntimeException('Invalid is_active payload.');
            }
            $isActive = $body['is_active'];
        }

        // 2) Execute service
        $this->commandService->createCity(new CreateCityCommand(
            countryId: $countryId,
            name:      $name,
            code:      $code,
            isActive:  $isActive,
        ));

        // 3) Return success
        return $this->json->success($response);
    }
}

