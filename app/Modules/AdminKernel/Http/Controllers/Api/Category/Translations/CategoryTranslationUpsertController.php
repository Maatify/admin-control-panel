<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\Category\Translations;

use Maatify\AdminKernel\Domain\Category\Validation\CategoryTranslationUpsertSchema;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\Category\Command\UpsertCategoryTranslationCommand;
use Maatify\Category\Service\CategoryCommandService;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class CategoryTranslationUpsertController
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

        $this->validationGuard->check(new CategoryTranslationUpsertSchema(), $body);

        $languageId             = $body['language_id'];
        $translatedName         = $body['translated_name'];
        $translatedDescription  = isset($body['translated_description']) && is_string($body['translated_description'])
            ? $body['translated_description']
            : null;

        if (!is_int($languageId) || !is_string($translatedName)) {
            throw new \RuntimeException('Invalid validated payload.');
        }

        $this->commandService->upsertTranslation(new UpsertCategoryTranslationCommand(
            categoryId:            $categoryId,
            languageId:            $languageId,
            translatedName:        $translatedName,
            translatedDescription: $translatedDescription !== '' ? $translatedDescription : null,
        ));

        return $this->json->success($response);
    }
}

