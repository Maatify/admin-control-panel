<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\Category\Translations;

use Maatify\AdminKernel\Domain\Category\Validation\CategoryTranslationDeleteSchema;
use Maatify\AdminKernel\Domain\Exception\AdminKernelValidationException;
use Maatify\AdminKernel\Domain\Exception\EntityNotFoundException;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\Category\Command\DeleteCategoryTranslationCommand;
use Maatify\Category\Exception\CategoryNotFoundException;
use Maatify\Category\Exception\CategoryPersistenceException;
use Maatify\Category\Exception\CategoryTranslationNotFoundException;
use Maatify\Category\Service\CategoryCommandService;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class CategoryTranslationDeleteController
{
    public function __construct(
        private CategoryCommandService $commandService,
        private ValidationGuard $validationGuard,
        private JsonResponseFactory $json
    ) {}

    /** @param array<string, string> $args */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $categoryId = (int) $args['category_id'];

        /** @var array<string, mixed> $body */
        $body = (array) $request->getParsedBody();

        $this->validationGuard->check(new CategoryTranslationDeleteSchema(), $body);

        $languageId = $body['language_id'];

        if (!is_int($languageId)) {
            throw new AdminKernelValidationException(
                sprintf('Field "language_id" has unexpected type %s.', get_debug_type($body['language_id']))
            );
        }

        try {
            $this->commandService->deleteTranslation(new DeleteCategoryTranslationCommand(
                categoryId: $categoryId,
                languageId: $languageId,
            ));
        } catch (CategoryNotFoundException $e) {
            throw new EntityNotFoundException('Category', $categoryId);
        } catch (CategoryTranslationNotFoundException $e) {
            throw new EntityNotFoundException('Category translation', $categoryId);
        } catch (CategoryPersistenceException $e) {
            throw $e;
        }

        return $this->json->success($response);
    }
}
