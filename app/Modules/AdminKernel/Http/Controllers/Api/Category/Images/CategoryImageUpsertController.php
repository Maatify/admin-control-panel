<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\Category\Images;

use Maatify\AdminKernel\Domain\Category\Validation\CategoryImageUpsertSchema;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\Category\Command\UpsertCategoryImageCommand;
use Maatify\Category\Enum\CategoryImageTypeEnum;
use Maatify\Category\Service\CategoryCommandService;
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
        $category_id = (int) $args['category_id'];
        /** @var array<string, mixed> $body */
        $body = (array) $request->getParsedBody();

        $this->validationGuard->check(new CategoryImageUpsertSchema(), $body);

        $imageType  = $body['image_type'];
        $languageId = $body['language_id'];
        $path       = $body['path'];

        if (!is_string($imageType) || !is_int($languageId) || !is_string($path)) {
            throw new \RuntimeException('Invalid validated payload.');
        }

        $result = $this->commandService->upsertImage(new UpsertCategoryImageCommand(
            categoryId: $category_id,
            imageType:  CategoryImageTypeEnum::from($imageType),
            languageId: $languageId,
            path:       $path,
        ));

        return $this->json->data($response, $result);
    }
}

