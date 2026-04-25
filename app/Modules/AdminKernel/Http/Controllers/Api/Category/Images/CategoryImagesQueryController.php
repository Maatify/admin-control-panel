<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\Category\Images;

use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\Category\Service\CategoryQueryService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class CategoryImagesQueryController
{
    public function __construct(
        private CategoryQueryService $queryService,
        private JsonResponseFactory  $json,
    ) {}

    /** @param array<string, string> $args */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $images = $this->queryService->listImages((int) $args['category_id']);

        return $this->json->data($response, $images);
    }
}

