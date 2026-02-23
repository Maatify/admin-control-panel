<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-22 21:06
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Ui\ContentDocuments;

use Maatify\AdminKernel\Context\AdminContext;
use Maatify\AdminKernel\Domain\Exception\IdentifierNotFoundException;
use Maatify\AdminKernel\Domain\Service\AuthorizationService;
use Maatify\ContentDocuments\Domain\Contract\Repository\DocumentRepositoryInterface;
use Maatify\Exceptions\Exception\Validation\InvalidArgumentMaatifyException;
use Maatify\LanguageCore\Contract\LanguageRepositoryInterface;
use Maatify\LanguageCore\Contract\LanguageSettingsRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final readonly class UiContentDocumentTranslationsController
{
    public function __construct(
        private Twig $twig,
        private AuthorizationService $authorization,
        private DocumentRepositoryInterface $reader,
        private LanguageRepositoryInterface $languageRepository,
        private LanguageSettingsRepositoryInterface $settingsRepository
    )
    {
    }

    /**
     * @param   array{type_id?: string, document_id?: string}  $args
     */
    public function index(Request $request, Response $response, array $args): Response
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

        $versionDetails = $this->reader->findById($documentId);
        if ($versionDetails == null || $versionDetails->documentTypeId !== $typeId) {
            throw new IdentifierNotFoundException('Document not found.');
        }

        $languages = $this->languageRepository->listAll();

        $languagesData = [];

        foreach ($languages->items as $language) {
            $settings = $this->settingsRepository->getByLanguageId($language->id);

            // Safety rule:
            // Language without settings MUST NOT appear in UI select
            if ($settings === null) {
                continue;
            }

            $languagesData[] = [
                'id'         => $language->id,
                'code'       => $language->code,
                'name'       => $language->name,
                'direction'  => $settings->direction->value,
                'icon'       => $settings->icon,
                'is_default' => $language->fallbackLanguageId === null,
            ];
        }

        /** @var AdminContext $context */
        $context = $request->getAttribute(AdminContext::class);
        $adminId = $context->adminId;

        $capabilities = [
            'can_view_types' => $this->authorization->hasPermission($adminId, 'content_documents.types.query'),
            'can_view_versions' => $this->authorization->hasPermission($adminId, 'content_documents.versions.query'),
            'can_view_translation_details' => $this->authorization->hasPermission($adminId, 'content_documents.translations.details'),
        ];

        return $this->twig->render(
            $response,
            'pages/content-documents/content_document_translations.list.twig',
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
                'languages'    => $languagesData,
                'capabilities' => $capabilities,
            ]
        );
    }
}
