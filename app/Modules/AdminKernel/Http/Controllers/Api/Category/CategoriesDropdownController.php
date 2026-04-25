<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\Category;

use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\Category\Service\CategoryQueryService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Returns the active ROOT categories only (parent_id IS NULL).
 * Used to populate top-level category dropdowns in the UI.
 * To get sub-categories of a specific root, use SubCategoriesDropdownController.
 */
final readonly class CategoriesDropdownController
{
    public function __construct(
        private CategoryQueryService $queryService,
        private JsonResponseFactory  $json,
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $list = $this->queryService->activeRootList();

        return $this->json->data($response, ['data' => $list]);
    }
}

