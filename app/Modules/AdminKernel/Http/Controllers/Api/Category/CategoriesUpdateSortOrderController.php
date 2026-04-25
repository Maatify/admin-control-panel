<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\Category;

use Maatify\AdminKernel\Domain\Category\Validation\CategoryUpdateSortOrderSchema;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
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

        $this->validationGuard->check(new CategoryUpdateSortOrderSchema(), $body);

        $id           = $body['id'];
        $displayOrder = $body['display_order'];

        if (!is_int($id) || !is_int($displayOrder)) {
            throw new \RuntimeException('Invalid validated payload.');
        }

        // parent_id is optional — scopes the reorder to root or sub-category level
        $parentId = null;
        if (array_key_exists('parent_id', $body)) {
            if (!is_int($body['parent_id']) && $body['parent_id'] !== null) {
                throw new \RuntimeException('Invalid parent_id payload.');
            }
            $parentId = $body['parent_id'];
        }

        $this->commandService->reorder($id, $displayOrder, $parentId);

        return $this->json->success($response);
    }
}

