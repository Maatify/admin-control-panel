<?php

declare(strict_types=1);

namespace Maatify\Category\Admin\Http\Controllers\Api;

use Maatify\AdminKernel\Domain\Exception\AdminKernelValidationException;
use Maatify\AdminKernel\Domain\Exception\EntityAlreadyExistsException;
use Maatify\AdminKernel\Domain\Exception\EntityNotFoundException;
use Maatify\AdminKernel\Domain\Exception\InvalidOperationException;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\Category\Admin\Domain\Validation\CategoryCreateSchema;
use Maatify\Category\Command\CreateCategoryCommand;
use Maatify\Category\Exception\CategoryDepthExceededException;
use Maatify\Category\Exception\CategoryInvalidArgumentException;
use Maatify\Category\Exception\CategoryNotFoundException;
use Maatify\Category\Exception\CategoryPersistenceException;
use Maatify\Category\Exception\CategorySlugAlreadyExistsException;
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
            throw new AdminKernelValidationException(
                sprintf('Field "name/slug" has unexpected type %s.', get_debug_type($body))
            );
        }

        $parentId = null;
        if (array_key_exists('parent_id', $body)) {
            if (!is_int($body['parent_id']) && $body['parent_id'] !== null) {
                throw new AdminKernelValidationException(
                    sprintf('Field "parent_id" has unexpected type %s.', get_debug_type($body['parent_id']))
                );
            }
            $parentId = $body['parent_id'];
        }

        $isActive = true;
        if (array_key_exists('is_active', $body)) {
            if (!is_bool($body['is_active'])) {
                throw new AdminKernelValidationException(
                    sprintf('Field "is_active" has unexpected type %s.', get_debug_type($body['is_active']))
                );
            }
            $isActive = $body['is_active'];
        }

        $displayOrder = 0;
        if (array_key_exists('display_order', $body)) {
            if (!is_int($body['display_order'])) {
                throw new AdminKernelValidationException(
                    sprintf('Field "display_order" has unexpected type %s.', get_debug_type($body['display_order']))
                );
            }
            $displayOrder = $body['display_order'];
        }

        $notes = null;
        if (array_key_exists('notes', $body)) {
            if (!is_string($body['notes']) && $body['notes'] !== null) {
                throw new AdminKernelValidationException(
                    sprintf('Field "notes" has unexpected type %s.', get_debug_type($body['notes']))
                );
            }
            $notes = is_string($body['notes']) ? $body['notes'] : null;
        }

        $description = null;
        if (array_key_exists('description', $body)) {
            if (!is_string($body['description']) && $body['description'] !== null) {
                throw new AdminKernelValidationException(
                    sprintf('Field "description" has unexpected type %s.', get_debug_type($body['description']))
                );
            }
            $description = is_string($body['description']) && $body['description'] !== '' ? $body['description'] : null;
        }

        try {
            $result = $this->commandService->create(new CreateCategoryCommand(
                name:         $name,
                slug:         $slug,
                description:  $description,
                parentId:     $parentId,
                isActive:     $isActive,
                displayOrder: $displayOrder,
                notes:        $notes,
            ));
        } catch (CategorySlugAlreadyExistsException $e) {
            throw new EntityAlreadyExistsException('Category', 'slug', $slug);
        } catch (CategoryNotFoundException $e) {
            throw new EntityNotFoundException('Category', (string) $parentId);
        } catch (CategoryDepthExceededException $e) {
            throw new InvalidOperationException('Category', 'create', $e->getMessage());
        } catch (CategoryInvalidArgumentException $e) {
            throw new AdminKernelValidationException($e->getMessage());
        } catch (CategoryPersistenceException $e) {
            throw $e;
        }

        return $this->json->data($response, $result);
    }
}

