<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\Category;

use Maatify\AdminKernel\Domain\Category\Validation\CategoryUpdateSchema;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\Category\Command\UpdateCategoryCommand;
use Maatify\Category\Service\CategoryCommandService;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class CategoriesUpdateController
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

        $this->validationGuard->check(new CategoryUpdateSchema(), $body);

        $id           = $body['id'];
        $name         = $body['name'];
        $slug         = $body['slug'];
        $isActive     = $body['is_active'];
        $displayOrder = $body['display_order'];

        if (!is_int($id) || !is_string($name) || !is_string($slug) || !is_bool($isActive) || !is_int($displayOrder)) {
            throw new \RuntimeException('Invalid validated payload.');
        }

        $parentId = null;
        if (array_key_exists('parent_id', $body)) {
            if (!is_int($body['parent_id']) && $body['parent_id'] !== null) {
                throw new \RuntimeException('Invalid parent_id payload.');
            }
            $parentId = $body['parent_id'];
        }

        $notes = null;
        if (array_key_exists('notes', $body)) {
            if (!is_string($body['notes']) && $body['notes'] !== null) {
                throw new \RuntimeException('Invalid notes payload.');
            }
            $notes = is_string($body['notes']) ? $body['notes'] : null;
        }

        $description = null;
        if (array_key_exists('description', $body)) {
            if (!is_string($body['description']) && $body['description'] !== null) {
                throw new \RuntimeException('Invalid description payload.');
            }
            $description = is_string($body['description']) && $body['description'] !== '' ? $body['description'] : null;
        }

        $this->commandService->update(new UpdateCategoryCommand(
            id:           $id,
            name:         $name,
            slug:         $slug,
            parentId:     $parentId,
            isActive:     $isActive,
            displayOrder: $displayOrder,
            description:  $description,
            notes:        $notes,
        ));

        return $this->json->success($response);
    }
}

