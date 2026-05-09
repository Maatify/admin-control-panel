<?php

declare(strict_types=1);

namespace Maatify\GeoSlim\Admin\Http\Controllers\Api\Cities;

use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\Geo\Command\UpdateCityCommand;
use Maatify\Geo\Service\GeoCommandService;
use Maatify\GeoSlim\Admin\Domain\Validation\CityUpdateSchema;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class CitiesUpdateController
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
        $this->validationGuard->check(new CityUpdateSchema(), $body);

        $id       = $body['id'];
        $name     = $body['name'];
        $isActive = $body['is_active'];

        if (!is_int($id) || !is_string($name) || !is_bool($isActive)) {
            throw new \RuntimeException('Invalid validated payload.');
        }

        $code = null;
        if (array_key_exists('code', $body) && is_string($body['code'])) {
            $code = $body['code'];
        }

        $timeZone = null;
        if (array_key_exists('time_zone', $body) && is_string($body['time_zone'])) {
            $timeZone = $body['time_zone'];
        }

        // 2) Execute service
        $this->commandService->updateCity(new UpdateCityCommand(
            id:       $id,
            name:     $name,
            code:     $code,
            timeZone: $timeZone,
            isActive: $isActive,
        ));

        // 3) Return success
        return $this->json->success($response);
    }
}

