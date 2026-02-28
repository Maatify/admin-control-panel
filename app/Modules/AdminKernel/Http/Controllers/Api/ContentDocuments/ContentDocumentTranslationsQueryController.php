<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-23 04:17
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\ContentDocuments;

use Maatify\AdminKernel\Domain\ContentDocuments\ContentDocumentTranslationQueryReaderInterface;
use Maatify\AdminKernel\Domain\ContentDocuments\List\ContentDocumentTranslationsListCapabilities;
use Maatify\AdminKernel\Domain\Exception\IdentifierNotFoundException;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\AdminKernel\Infrastructure\Query\ListFilterResolver;
use Maatify\ContentDocuments\Domain\Contract\Repository\DocumentRepositoryInterface;
use Maatify\Exceptions\Exception\Validation\InvalidArgumentMaatifyException;
use Maatify\Validation\Guard\ValidationGuard;
use Maatify\Validation\Schemas\SharedListQuerySchema;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class ContentDocumentTranslationsQueryController
{
    public function __construct(
        private ContentDocumentTranslationQueryReaderInterface $reader,
        private DocumentRepositoryInterface $documentRepository,
        private ValidationGuard $validationGuard,
        private ListFilterResolver $filterResolver,
        private JsonResponseFactory $json,
    )
    {
    }

    /**
     * @param   array{type_id?: string, document_id?: string}  $args
     */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $typeId = (int) ($args['type_id'] ?? 0);
        $documentId = (int) ($args['document_id'] ?? 0);

        if ($typeId <= 0) {
            // invalid route arg (type_id)
            throw new InvalidArgumentMaatifyException('Invalid type_id.');
        }

        if ($documentId <= 0) {
            throw new InvalidArgumentMaatifyException('Invalid document_id.');
        }

        $versionDetails = $this->documentRepository->findById($documentId);
        if ($versionDetails == null || $versionDetails->documentTypeId !== $typeId) {
            throw new IdentifierNotFoundException('Document not found.');
        }

        /** @var array<string,mixed> $body */
        $body = (array)$request->getParsedBody();

        // 1) Validate request shape
        $this->validationGuard->check(new SharedListQuerySchema(), $body);

        /**
         * @var array{
         *   page?: int,
         *   per_page?: int,
         *   search?: array{
         *     global?: string,
         *     columns?: array<string, string>
         *   },
         *   date?: array{
         *     from?: string,
         *     to?: string
         *   }
         * } $validated
         */
        $validated = $body;

        // 2) Build canonical ListQueryDTO
        $query = ListQueryDTO::fromArray($validated);

        // 3) Capabilities
        $capabilities = ContentDocumentTranslationsListCapabilities::define();

        // 4) Resolve filters
        $filters = $this->filterResolver->resolve($query, $capabilities);

        // 5) Execute reader
        $result = $this->reader->query($documentId, $query, $filters);

        // 6) Return JSON
        return $this->json->data($response, $result);
    }
}
