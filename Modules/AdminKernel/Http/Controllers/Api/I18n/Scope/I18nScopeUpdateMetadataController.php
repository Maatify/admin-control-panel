<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-10-01 00:00
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\I18n\Scope;

use Maatify\AdminKernel\Domain\I18n\Scope\Validation\I18nScopeUpdateMetadataSchema;
use Maatify\AdminKernel\Domain\I18n\Service\I18nScopeCommandService;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class I18nScopeUpdateMetadataController
{
    public function __construct(
        private I18nScopeCommandService $service,
        private ValidationGuard $validationGuard,
        private JsonResponseFactory $json,
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        /** @var array<string,mixed> $body */
        $body = (array) $request->getParsedBody();

        $this->validationGuard->check(
            new I18nScopeUpdateMetadataSchema(),
            $body
        );

        $id = 0;
        if (isset($body['id']) && is_numeric($body['id'])) {
            $id = (int) $body['id'];
        }

        $name = null;
        if (isset($body['name']) && is_string($body['name'])) {
            $name = $body['name'];
        }

        $description = null;
        if (isset($body['description']) && is_string($body['description'])) {
            $description = $body['description'];
        }

        // at least one field; existence is decided under lock by the I18n package
        $this->service->updateMetadata($id, $name, $description);

        return $this->json->data($response, ['status' => 'ok']);
    }
}
