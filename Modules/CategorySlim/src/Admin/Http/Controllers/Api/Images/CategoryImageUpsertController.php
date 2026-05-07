<?php

declare(strict_types=1);

namespace Maatify\CategorySlim\Admin\Http\Controllers\Api\Images;

use Maatify\AdminKernel\Domain\Exception\AdminKernelValidationException;
use Maatify\AdminKernel\Domain\Exception\EntityNotFoundException;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\Category\Command\UpsertCategoryImageCommand;
use Maatify\Category\Enum\CategoryImageTypeEnum;
use Maatify\Category\Exception\CategoryInvalidArgumentException;
use Maatify\Category\Exception\CategoryNotFoundException;
use Maatify\Category\Exception\CategoryPersistenceException;
use Maatify\Category\Service\CategoryCommandService;
use Maatify\CategorySlim\Admin\Domain\Validation\CategoryImageUpsertSchema;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class CategoryImageUpsertController
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

        $this->validationGuard->check(new CategoryImageUpsertSchema(), $body);

        $imageType  = $body['image_type'];
        $languageId = $body['language_id'];
        $path       = $body['path'];

        if (!is_string($imageType) || !is_int($languageId) || !is_string($path)) {
            throw new AdminKernelValidationException(
                sprintf('Field "image_type/language_id/path" has unexpected type %s.', get_debug_type($body))
            );
        }

        try {
            $result = $this->commandService->upsertImage(new UpsertCategoryImageCommand(
                categoryId: $categoryId,
                imageType:  CategoryImageTypeEnum::fromString($imageType),
                languageId: $languageId,
                path:       $path,
            ));
        } catch (CategoryNotFoundException $e) {
            throw new EntityNotFoundException('Category', $categoryId);
        } catch (CategoryInvalidArgumentException $e) {
            throw new AdminKernelValidationException($e->getMessage());
        } catch (CategoryPersistenceException $e) {
            throw $e;
        }

        return $this->json->data($response, $result);
    }
}

