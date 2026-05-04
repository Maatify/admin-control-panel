<?php

declare(strict_types=1);

namespace Maatify\Category\Admin\Http\Controllers\Api;

use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\Category\Admin\Domain\Validation\CategoryUpdateSortOrderSchema;
use Maatify\Category\Service\CategoryCommandService;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class CategoriesUpdateSortOrderController
{
    public function __construct(
        private CategoryCommandService $commandService,
        private ValidationGuard        $validationGuard,
        private JsonResponseFactory    $json,
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        /** @var array<string, mixed> $body */
        $body = (array) $request->getParsedBody();

        // 1) Validate request
        $this->validationGuard->check(new CategoryUpdateSortOrderSchema(), $body);

        $id           = $body['id'];
        $displayOrder = $body['display_order'];

        if (!is_int($id) || !is_int($displayOrder)) {
            throw new \RuntimeException('Invalid validated payload.');
        }

        // 2) parent_id is optional — scopes the reorder to root or sub-category level
        $parentId = isset($body['parent_id']) && is_int($body['parent_id']) ? $body['parent_id'] : null;

        // 3) Execute service
        $this->commandService->reorder($id, $displayOrder, $parentId);

        // 4) Return success
        return $this->json->success($response);
    }
}

