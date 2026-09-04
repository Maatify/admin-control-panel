<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-20 00:55
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\ContentDocuments;

use Maatify\AdminKernel\Domain\ContentDocuments\Validation\ContentDocumentTypesCreateSchema;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\ContentDocuments\Domain\Contract\Service\ContentDocumentsFacadeInterface;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentTypeKey;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class ContentDocumentTypesCreateController
{
    public function __construct(
        private ContentDocumentsFacadeInterface $facade,
        private ValidationGuard $validationGuard,
        private JsonResponseFactory $json,
    )
    {
    }


    public function __invoke(Request $request, Response $response): Response
    {
        /** @var array<string,mixed> $body */
        $body = (array) $request->getParsedBody();

        // 1) Validate request body
        $this->validationGuard->check(new ContentDocumentTypesCreateSchema(), $body);

        /** @var array{
         *   key: string,
         *   requires_acceptance_default: bool,
         *   is_system: bool
         * } $body
         */

        $key = new DocumentTypeKey($body['key']);
        
        // 2) Execute creation
        $this->facade->createDocumentType(
            key: $key,
            requiresAcceptanceDefault: $body['requires_acceptance_default'],
            isSystem: $body['is_system']
        );

        // 3) Success — no payload
        return $this->json->success($response);
    }
}
