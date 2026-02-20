<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-20 01:35
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\ContentDocuments;

use Maatify\AdminKernel\Domain\ContentDocuments\Validation\ContentDocumentTypesUpdateSchema;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\ContentDocuments\Domain\Contract\Service\ContentDocumentsFacadeInterface;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class ContentDocumentTypesUpdateController
{
    public function __construct(
        private ContentDocumentsFacadeInterface $facade,
        private ValidationGuard $validationGuard,
        private JsonResponseFactory $json,
    )
    {
    }

    /**
     * @param array<string,string> $args
     */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['type_id'] ?? 0);

        /** @var array<string,mixed> $body */
        $body = (array) $request->getParsedBody();

        // 1) Validate request body
        $this->validationGuard->check(new ContentDocumentTypesUpdateSchema(), $body);

        /** @var array{
         *   requires_acceptance_default: bool,
         *   is_system: bool
         * } $body
         */

        // 2) Execute creation
        $this->facade->updateDocumentType(
            typeId: $id,
            requiresAcceptanceDefault: $body['requires_acceptance_default'],
            isSystem: $body['is_system']
        );

        // 3) Success — no payload
        return $this->json->success($response);
    }

}
