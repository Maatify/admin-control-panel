<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\Category;

use Maatify\AdminKernel\Domain\Category\Validation\CategoryCreateSchema;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\Category\Command\CreateCategoryCommand;
use Maatify\Category\Service\CategoryCommandService;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class CategoriesCreateController
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

        $this->validationGuard->check(new CategoryCreateSchema(), $body);

        $name = $body['name'];
        $slug = $body['slug'];

        if (!is_string($name) || !is_string($slug)) {
            throw new \RuntimeException('Invalid validated payload.');
        }

        $parentId = null;
        if (array_key_exists('parent_id', $body)) {
            if (!is_int($body['parent_id']) && $body['parent_id'] !== null) {
                throw new \RuntimeException('Invalid parent_id payload.');
            }
            $parentId = $body['parent_id'];
        }

        $isActive = true;
        if (array_key_exists('is_active', $body)) {
            if (!is_bool($body['is_active'])) {
                throw new \RuntimeException('Invalid is_active payload.');
            }
            $isActive = $body['is_active'];
        }

        $displayOrder = 0;
        if (array_key_exists('display_order', $body)) {
            if (!is_int($body['display_order'])) {
                throw new \RuntimeException('Invalid display_order payload.');
            }
            $displayOrder = $body['display_order'];
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

        $result = $this->commandService->create(new CreateCategoryCommand(
            name:         $name,
            slug:         $slug,
            description:  $description,
            parentId:     $parentId,
            isActive:     $isActive,
            displayOrder: $displayOrder,
            notes:        $notes,
        ));

        return $this->json->data($response, $result);
    }
}

