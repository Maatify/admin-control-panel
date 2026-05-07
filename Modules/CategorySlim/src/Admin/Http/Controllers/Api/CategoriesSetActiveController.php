<?php

declare(strict_types=1);

namespace Maatify\CategorySlim\Admin\Http\Controllers\Api;

use Maatify\AdminKernel\Domain\Exception\AdminKernelValidationException;
use Maatify\AdminKernel\Domain\Exception\EntityNotFoundException;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\Category\Command\UpdateCategoryStatusCommand;
use Maatify\Category\Exception\CategoryNotFoundException;
use Maatify\Category\Exception\CategoryPersistenceException;
use Maatify\Category\Service\CategoryCommandService;
use Maatify\CategorySlim\Admin\Domain\Validation\CategorySetActiveSchema;
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
            throw new AdminKernelValidationException(
                sprintf('Field "id/is_active" has unexpected type %s.', get_debug_type($body))
            );
        }

        try {
            $this->commandService->updateStatus(new UpdateCategoryStatusCommand(
                id:       $id,
                isActive: $isActive,
            ));
        } catch (CategoryNotFoundException $e) {
            throw new EntityNotFoundException('Category', $id);
        } catch (CategoryPersistenceException $e) {
            throw $e;
        }

        return $this->json->success($response);
    }
}

