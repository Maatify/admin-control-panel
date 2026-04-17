<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\ImageProfile;

use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\ImageProfile\Service\ImageProfileQueryService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class ImageProfilesDropdownController
{
    public function __construct(
        private ImageProfileQueryService $queryService,
        private JsonResponseFactory $json,
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $list = $this->queryService->activeList();

        return $this->json->data($response, ['data' => $list]);
    }
}
