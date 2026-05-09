<?php

declare(strict_types=1);

namespace Maatify\GeoSlim\Admin\Http\Controllers\Api\Cities;

use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\Geo\Service\GeoCommandService;
use Maatify\GeoSlim\Admin\Domain\Validation\CityUpdateSortOrderSchema;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class CitiesUpdateSortOrderController
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
        $this->validationGuard->check(new CityUpdateSortOrderSchema(), $body);

        $id           = $body['id'];
        $displayOrder = $body['display_order'];

        if (!is_int($id) || !is_int($displayOrder)) {
            throw new \RuntimeException('Invalid validated payload.');
        }

        // 2) Execute service
        $this->commandService->reorderCity($id, $displayOrder);

        // 3) Return success
        return $this->json->success($response);
    }
}

