<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\Category\Settings;

use Maatify\AdminKernel\Domain\Category\Validation\CategorySettingUpsertSchema;
use Maatify\AdminKernel\Domain\Exception\AdminKernelValidationException;
use Maatify\AdminKernel\Domain\Exception\EntityNotFoundException;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\Category\Command\UpsertCategorySettingCommand;
use Maatify\Category\Exception\CategoryNotFoundException;
use Maatify\Category\Exception\CategoryPersistenceException;
use Maatify\Category\Service\CategoryCommandService;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class CategorySettingUpsertController
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

        $this->validationGuard->check(new CategorySettingUpsertSchema(), $body);

        $key   = $body['key'];
        $value = $body['value'];

        if (!is_string($key) || !is_string($value)) {
            throw new AdminKernelValidationException(
                sprintf('Field "key/value" has unexpected type %s.', get_debug_type($body))
            );
        }

        try {
            $dto = $this->commandService->upsertSetting(new UpsertCategorySettingCommand(
                categoryId: $category_id,
                key:        $key,
                value:      $value,
            ));
        } catch (CategoryNotFoundException $e) {
            throw new EntityNotFoundException('Category', $category_id);
        } catch (CategoryPersistenceException $e) {
            throw $e;
        }

        return $this->json->data($response, $dto);
    }
}
