<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\Category\SubCategories;

use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\Category\Service\CategoryQueryService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Returns active sub-categories of a specific parent category.
 * Used to populate the second-level dropdown after a root category is selected.
 */
final readonly class SubCategoriesDropdownController
{
    public function __construct(
        private CategoryQueryService $queryService,
        private JsonResponseFactory  $json,
    ) {}

    /** @param array<string, string> $args */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $list = $this->queryService->activeSubList((int) $args['category_id']);

        return $this->json->data($response, ['data' => $list]);
    }
}

