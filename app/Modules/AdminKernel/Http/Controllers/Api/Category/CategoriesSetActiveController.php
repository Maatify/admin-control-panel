<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\Category;

use Maatify\AdminKernel\Domain\Category\Validation\CategorySetActiveSchema;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\Category\Command\UpdateCategoryStatusCommand;
use Maatify\Category\Service\CategoryCommandService;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class CategoriesSetActiveController
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

        $this->validationGuard->check(new CategorySetActiveSchema(), $body);

        $id       = $body['id'];
        $isActive = $body['is_active'];

        if (!is_int($id) || !is_bool($isActive)) {
            throw new \RuntimeException('Invalid validated payload.');
        }

        $this->commandService->updateStatus(new UpdateCategoryStatusCommand(
            id:       $id,
            isActive: $isActive,
        ));

        return $this->json->success($response);
    }
}

