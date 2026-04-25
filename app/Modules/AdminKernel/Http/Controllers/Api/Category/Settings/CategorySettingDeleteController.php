<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\Category\Settings;

use Maatify\AdminKernel\Domain\Category\Validation\CategorySettingDeleteSchema;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\Category\Command\DeleteCategorySettingCommand;
use Maatify\Category\Service\CategoryCommandService;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class CategorySettingDeleteController
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

        $this->validationGuard->check(new CategorySettingDeleteSchema(), $body);

        $key = $body['key'];

        if (!is_string($key)) {
            throw new \RuntimeException('Invalid validated payload.');
        }

        $this->commandService->deleteSetting(new DeleteCategorySettingCommand(
            categoryId: $category_id,
            key:        $key,
        ));

        return $this->json->success($response);
    }
}

