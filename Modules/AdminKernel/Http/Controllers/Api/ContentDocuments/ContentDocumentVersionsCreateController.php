<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-22 00:16
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\ContentDocuments;

use Maatify\AdminKernel\Domain\ContentDocuments\Validation\ContentDocumentVersionsCreateSchema;
use Maatify\AdminKernel\Domain\Exception\EntityNotFoundException;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\ContentDocuments\Domain\Contract\Service\ContentDocumentsFacadeInterface;
use Maatify\ContentDocuments\Domain\DTO\DocumentTypeDTO;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentTypeKey;
use Maatify\ContentDocuments\Domain\ValueObject\DocumentVersion;
use Maatify\Exceptions\Exception\Validation\InvalidArgumentMaatifyException;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class ContentDocumentVersionsCreateController
{
    public function __construct(
        private ContentDocumentsFacadeInterface $facade,
        private ValidationGuard $validationGuard,
        private JsonResponseFactory $json,
    ) {
    }

    /**
     * @param array{type_id?: string} $args
     */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['type_id'] ?? 0);

        if ($id <= 0) {
            // invalid route arg (type_id)
            throw new InvalidArgumentMaatifyException('Invalid type_id.');
        }

        /** @var DocumentTypeDTO|null $details */
        $details = $this->facade->getDocumentTypeById($id);

        if($details == null){
            throw new EntityNotFoundException('typeId', $id);
        }

        /** @var array<string,mixed> $body */
        $body = (array) $request->getParsedBody();

        // 1) Validate request shape
        $this->validationGuard->check(new ContentDocumentVersionsCreateSchema(), $body);

        /** @var array{version: string, requires_acceptance: bool} $validated */
        $validated = $body;

        $typeKey = new DocumentTypeKey($details->key);
        $version = new DocumentVersion($validated['version']);

        // 2) Create document version (draft)
        $this->facade->createVersion(
            $typeKey,
            $version,
            $validated['requires_acceptance']
        );

        // 3) Success — no payload
        return $this->json->success($response);
    }
}