<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-24 00:08
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Ui\ContentDocuments;

use Maatify\AdminKernel\Context\AdminContext;
use Maatify\AdminKernel\Domain\Exception\IdentifierNotFoundException;
use Maatify\AdminKernel\Domain\LanguageCore\LanguageWithSettingsListReaderInterface;
use Maatify\AdminKernel\Domain\Service\AuthorizationService;
use Maatify\AdminKernel\Domain\Support\LanguageCollectionHelper;
use Maatify\ContentDocuments\Domain\Contract\Repository\DocumentRepositoryInterface;
use Maatify\ContentDocuments\Domain\Contract\Service\ContentDocumentsFacadeInterface;
use Maatify\ContentDocuments\Domain\DTO\DocumentTranslationDTO;
use Maatify\Exceptions\Exception\Validation\InvalidArgumentMaatifyException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final readonly class UiContentDocumentTranslationsUpdateController
{
    public function __construct(
        private Twig $twig,
        private AuthorizationService $authorization,
        private DocumentRepositoryInterface $reader,
        private ContentDocumentsFacadeInterface $facade,
        private LanguageWithSettingsListReaderInterface $languageWithSettingsListReader,
    )
    {
    }

    /**
     * @param   array{type_id?: string, document_id?: string, language_id?: string}  $args
     */
    public function index(Request $request, Response $response, array $args): Response
    {
        $typeId = (int) ($args['type_id'] ?? 0);
        $documentId = (int) ($args['document_id'] ?? 0);
        $languageId = (int) ($args['language_id'] ?? 0);

        if ($typeId <= 0) {
            // invalid route arg (type_id)
            throw new InvalidArgumentMaatifyException('Invalid type_id.');
        }

        if ($documentId <= 0) {
            throw new InvalidArgumentMaatifyException('Invalid document_id.');
        }

        if($languageId <= 0){
            throw new InvalidArgumentMaatifyException('Invalid language_id.');
        }

        $versionDetails = $this->reader->findById($documentId);
        if ($versionDetails == null || $versionDetails->documentTypeId !== $typeId) {
            throw new IdentifierNotFoundException('Document not found.');
        }

        $languagesResponse = $this->languageWithSettingsListReader->listAll();
        $languages = $languagesResponse->items;

        $currentLanguage = LanguageCollectionHelper::findById($languages, $languageId);

        if ($currentLanguage === null) {
            throw new IdentifierNotFoundException('Language settings not found.');
        }

        $translationDetails = $this->facade->getTranslation($documentId, $languageId);
        if ($translationDetails === null) {
            $translationDetails = new DocumentTranslationDTO(
                documentId: $documentId,
                languageId: $languageId,
                title: '',
                metaTitle: '',
                metaDescription: '',
                content: '',
                createdAt: null,
                updatedAt: null
            );
        }

        /** @var AdminContext $context */
        $context = $request->getAttribute(AdminContext::class);
        $adminId = $context->adminId;

        $capabilities = [
            'can_view_types' => $this->authorization->hasPermission($adminId, 'content_documents.types.query'),
            'can_view_versions' => $this->authorization->hasPermission($adminId, 'content_documents.versions.query'),
            'can_view_translations' => $this->authorization->hasPermission($adminId, 'content_documents.translations.query'),
            'can_upsert' => $this->authorization->hasPermission($adminId, 'content_documents.translations.upsert'),
        ];

        return $this->twig->render(
            $response,
            'pages/content-documents/content_document_translations.details.twig',
            [
                'document_version' => [
                    'id' => $versionDetails->id,
                    'documentTypeId' => $versionDetails->documentTypeId,
                    'typeKey' => $versionDetails->typeKey,
                    'version' => $versionDetails->version,
                    'isActive' => $versionDetails->isActive,
                    'requiresAcceptance' => $versionDetails->requiresAcceptance,
                    'publishedAt' => $versionDetails->publishedAt,
                    'createdAt' => $versionDetails->createdAt,
                    'updatedAt' => $versionDetails->updatedAt,
                ],
                'language' => $currentLanguage,
                'languages' => $languages,
                'translation' => $translationDetails,
                'capabilities' => $capabilities,
            ]
        );
    }
}
