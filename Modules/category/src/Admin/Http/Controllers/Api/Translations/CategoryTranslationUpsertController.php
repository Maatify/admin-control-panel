<?php

declare(strict_types=1);

namespace Maatify\Category\Admin\Http\Controllers\Api\Translations;

use Maatify\AdminKernel\Domain\Exception\AdminKernelValidationException;
use Maatify\AdminKernel\Domain\Exception\EntityNotFoundException;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\Category\Admin\Domain\Validation\CategoryTranslationUpsertSchema;
use Maatify\Category\Command\UpsertCategoryTranslationCommand;
use Maatify\Category\Exception\CategoryInvalidArgumentException;
use Maatify\Category\Exception\CategoryNotFoundException;
use Maatify\Category\Exception\CategoryPersistenceException;
use Maatify\Category\Service\CategoryCommandService;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class CategoryTranslationUpsertController
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

        $this->validationGuard->check(new CategoryTranslationUpsertSchema(), $body);

        $languageId            = $body['language_id'];
        $translatedName        = $body['translated_name'];
        $translatedDescription = isset($body['translated_description']) && is_string($body['translated_description'])
            ? $body['translated_description']
            : null;

        if (!is_int($languageId) || !is_string($translatedName)) {
            throw new AdminKernelValidationException(
                sprintf('Field "language_id/translated_name" has unexpected type %s.', get_debug_type($body))
            );
        }

        try {
            $this->commandService->upsertTranslation(new UpsertCategoryTranslationCommand(
                categoryId:            $categoryId,
                languageId:            $languageId,
                translatedName:        $translatedName,
                translatedDescription: $translatedDescription !== '' ? $translatedDescription : null,
            ));
        } catch (CategoryNotFoundException $e) {
            throw new EntityNotFoundException('Category', $categoryId);
        } catch (CategoryInvalidArgumentException $e) {
            throw new AdminKernelValidationException($e->getMessage());
        } catch (CategoryPersistenceException $e) {
            throw $e;
        }

        return $this->json->success($response);
    }
}

