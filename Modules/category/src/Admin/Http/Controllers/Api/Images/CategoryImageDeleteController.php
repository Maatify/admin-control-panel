<?php

declare(strict_types=1);

namespace Maatify\Category\Admin\Http\Controllers\Api\Images;

use Maatify\AdminKernel\Domain\Exception\AdminKernelValidationException;
use Maatify\AdminKernel\Domain\Exception\EntityNotFoundException;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\Category\Admin\Domain\Validation\CategoryImageDeleteSchema;
use Maatify\Category\Command\DeleteCategoryImageCommand;
use Maatify\Category\Enum\CategoryImageTypeEnum;
use Maatify\Category\Exception\CategoryImageNotFoundException;
use Maatify\Category\Exception\CategoryInvalidArgumentException;
use Maatify\Category\Exception\CategoryNotFoundException;
use Maatify\Category\Exception\CategoryPersistenceException;
use Maatify\Category\Service\CategoryCommandService;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class CategoryImageDeleteController
{
    public function __construct(
        private CategoryCommandService $commandService,
        private ValidationGuard        $validationGuard,
        private JsonResponseFactory    $json,
    ) {}

    /** @param array<string, string> $args */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $categoryId = (int) $args['category_id'];

        /** @var array<string, mixed> $body */
        $body = (array) $request->getParsedBody();

        $this->validationGuard->check(new CategoryImageDeleteSchema(), $body);

        $imageType  = $body['image_type'];
        $languageId = $body['language_id'];

        if (!is_string($imageType) || !is_int($languageId)) {
            throw new AdminKernelValidationException(
                sprintf('Field "image_type/language_id" has unexpected type %s.', get_debug_type($body))
            );
        }

        try {
            $this->commandService->deleteImage(new DeleteCategoryImageCommand(
                categoryId: $categoryId,
                imageType:  CategoryImageTypeEnum::fromString($imageType),
                languageId: $languageId,
            ));
        } catch (CategoryNotFoundException $e) {
            throw new EntityNotFoundException('Category', $categoryId);
        } catch (CategoryImageNotFoundException $e) {
            throw new EntityNotFoundException('Category image', $categoryId);
        } catch (CategoryInvalidArgumentException $e) {
            throw new AdminKernelValidationException($e->getMessage());
        } catch (CategoryPersistenceException $e) {
            throw $e;
        }

        return $this->json->success($response);
    }
}

