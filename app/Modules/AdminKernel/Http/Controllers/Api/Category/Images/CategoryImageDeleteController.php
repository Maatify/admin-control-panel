<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\Category\Images;

use Maatify\AdminKernel\Domain\Category\Validation\CategoryImageDeleteSchema;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\Category\Command\DeleteCategoryImageCommand;
use Maatify\Category\Enum\CategoryImageTypeEnum;
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
        $category_id = (int) $args['category_id'];
        /** @var array<string, mixed> $body */
        $body = (array) $request->getParsedBody();

        $this->validationGuard->check(new CategoryImageDeleteSchema(), $body);

        $imageType  = $body['image_type'];
        $languageId = $body['language_id'];

        if (!is_string($imageType) || !is_int($languageId)) {
            throw new \RuntimeException('Invalid validated payload.');
        }

        $this->commandService->deleteImage(new DeleteCategoryImageCommand(
            categoryId: $category_id,
            imageType:  CategoryImageTypeEnum::from($imageType),
            languageId: $languageId,
        ));

        return $this->json->success($response);
    }
}

