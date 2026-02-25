<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-25 01:16
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\ContentDocuments;

use Maatify\AdminKernel\Domain\ContentDocuments\Validation\ContentDocumentTranslationsUpsertSchema;
use Maatify\AdminKernel\Domain\Exception\IdentifierNotFoundException;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\ContentDocuments\Domain\Contract\Repository\DocumentRepositoryInterface;
use Maatify\ContentDocuments\Domain\Contract\Service\ContentDocumentsFacadeInterface;
use Maatify\ContentDocuments\Domain\DTO\DocumentTranslationDTO;
use Maatify\Exceptions\Exception\Validation\InvalidArgumentMaatifyException;
use Maatify\LanguageCore\Contract\LanguageRepositoryInterface;
use Maatify\SharedCommon\Contracts\ClockInterface;
use Maatify\Validation\Guard\ValidationGuard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class ContentDocumentTranslationsUpsertController
{
    public function __construct(
        private ContentDocumentsFacadeInterface $facade,
        private DocumentRepositoryInterface $reader,
        private LanguageRepositoryInterface $languageRepository,
        private ValidationGuard $validationGuard,
        private JsonResponseFactory $json,
    )
    {
    }

    /**
     * @param   array{type_id?: string, document_id?: string, language_id?: string}  $args
     */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $typeId = (int)($args['type_id'] ?? 0);
        $documentId = (int)($args['document_id'] ?? 0);
        $languageId = (int)($args['language_id'] ?? 0);

        if ($typeId <= 0 || $documentId <= 0 || $languageId <= 0) {
            throw new InvalidArgumentMaatifyException('Invalid route parameters.');
        }

        /** @var array<string,mixed> $body */
        $body = (array)$request->getParsedBody();

        // 1) Validate request body
        $this->validationGuard->check(new ContentDocumentTranslationsUpsertSchema(), $body);

        /** @var array{
         *     title: string,
         *     meta_title: string,
         *     meta_description: string,
         *     content: string
         * } $body
         */

        $title = $body['title'];
        $metaTitle = trim($body['meta_title']);
        $metaDescription = trim($body['meta_description']);
        $content = $body['content'];


        $versionDetails = $this->reader->findById($documentId);
        if ($versionDetails == null || $versionDetails->documentTypeId !== $typeId) {
            throw new IdentifierNotFoundException('Document not found.');
        }

        $language = $this->languageRepository->getById($languageId);
        if ($language === null) {
            throw new IdentifierNotFoundException('Language not found.');
        }


        $translation = new DocumentTranslationDTO(
            documentId     : $documentId,
            languageId     : $languageId,
            title          : $title,
            metaTitle      : $metaTitle,
            metaDescription: $metaDescription,
            content        : $content,
            createdAt      : null,
            updatedAt      : null
        );
        // 2) Execute creation
        $this->facade->saveTranslation(
            $translation
        );

        // 3) Success — no payload
        return $this->json->success($response);
    }
}
