<?php

declare(strict_types=1);

namespace Maatify\GeoSlim\Admin\Http\Controllers\Api\Countries;

use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\Geo\Command\CreateCountryCommand;
use Maatify\Geo\Service\GeoCommandService;
use Maatify\GeoSlim\Admin\Domain\Validation\CountryCreateSchema;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class CountriesCreateController
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
        $this->validationGuard->check(new CountryCreateSchema(), $body);

        $code = $body['code'];
        $name = $body['name'];

        if (!is_string($code) || !is_string($name)) {
            throw new \RuntimeException('Invalid validated payload.');
        }

        $icon = null;
        if (array_key_exists('icon', $body) && is_string($body['icon'])) {
            $icon = $body['icon'];
        }

        $isActive = true;
        if (array_key_exists('is_active', $body)) {
            if (!is_bool($body['is_active'])) {
                throw new \RuntimeException('Invalid is_active payload.');
            }
            $isActive = $body['is_active'];
        }

        // 2) Execute service
        $this->commandService->createCountry(new CreateCountryCommand(
            code:     $code,
            name:     $name,
            icon:     $icon,
            isActive: $isActive,
        ));

        // 3) Return success
        return $this->json->success($response);
    }
}

