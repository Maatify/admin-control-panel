<?php

declare(strict_types=1);

namespace Maatify\CategorySlim\Admin\Http\Controllers\Api\Settings;

use Maatify\AdminKernel\Domain\Exception\AdminKernelValidationException;
use Maatify\AdminKernel\Domain\Exception\EntityNotFoundException;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\Category\Command\DeleteCategorySettingCommand;
use Maatify\Category\Exception\CategoryNotFoundException;
use Maatify\Category\Exception\CategoryPersistenceException;
use Maatify\Category\Exception\CategorySettingNotFoundException;
use Maatify\Category\Service\CategoryCommandService;
use Maatify\CategorySlim\Admin\Domain\Validation\CategorySettingDeleteSchema;
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
        $categoryId = (int) $args['category_id'];

        /** @var array<string, mixed> $body */
        $body = (array) $request->getParsedBody();

        $this->validationGuard->check(new CategorySettingDeleteSchema(), $body);

        $key = $body['key'];

        if (!is_string($key)) {
            throw new AdminKernelValidationException(
                sprintf('Field "key" has unexpected type %s.', get_debug_type($body['key']))
            );
        }

        try {
            $this->commandService->deleteSetting(new DeleteCategorySettingCommand(
                categoryId: $categoryId,
                key:        $key,
            ));
        } catch (CategoryNotFoundException $e) {
            throw new EntityNotFoundException('Category', $categoryId);
        } catch (CategorySettingNotFoundException $e) {
            throw new EntityNotFoundException('Category setting', $categoryId);
        } catch (CategoryPersistenceException $e) {
            throw $e;
        }

        return $this->json->success($response);
    }
}

