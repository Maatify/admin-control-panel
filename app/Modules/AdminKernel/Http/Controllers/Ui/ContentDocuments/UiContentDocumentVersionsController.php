<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-21 22:04
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Ui\ContentDocuments;

use Maatify\AdminKernel\Context\AdminContext;
use Maatify\AdminKernel\Domain\Exception\EntityNotFoundException;
use Maatify\AdminKernel\Domain\Service\AuthorizationService;
use Maatify\ContentDocuments\Domain\Contract\Service\ContentDocumentsFacadeInterface;
use Maatify\ContentDocuments\Domain\DTO\DocumentTypeDTO;
use Maatify\Exceptions\Exception\Validation\InvalidArgumentMaatifyException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final readonly class UiContentDocumentVersionsController
{
    public function __construct(
        private Twig $twig,
        private AuthorizationService $authorization,
        private ContentDocumentsFacadeInterface $facade,
    )
    {
    }

    /**
     * @param array{type_id?: string} $args
     */
    public function index(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['type_id'] ?? 0);

        if($id <= 0){
            throw new InvalidArgumentMaatifyException('Invalid type_id.');
        }

        /** @var DocumentTypeDTO|null $details*/
        $details = $this->facade->getDocumentTypeById($id);

        if(empty($details)){
            throw new EntityNotFoundException('typeId', $id);
        }

        /** @var AdminContext $context */
        $context = $request->getAttribute(AdminContext::class);
        $adminId = $context->adminId;

        $capabilities = [
            'can_view_types' => $this->authorization->hasPermission($adminId, 'content_documents.types.query'),
            'can_create' => $this->authorization->hasPermission($adminId, 'content_documents.versions.create'),
            'can_activate' => $this->authorization->hasPermission($adminId, 'content_documents.versions.activate'),
            'can_deactivate' => $this->authorization->hasPermission($adminId, 'content_documents.versions.deactivate'),
            'can_archive' => $this->authorization->hasPermission($adminId, 'content_documents.versions.archive'),
            'can_publish' => $this->authorization->hasPermission($adminId, 'content_documents.versions.publish'),
            'can_view_translations' => $this->authorization->hasPermission($adminId, 'content_documents.translations.query'),
        ];

        return $this->twig->render(
            $response,
            'pages/content-documents/content_document_versions.list.twig',
            [
                'document_type' => [
                    'id' => $details->id,
                    'key' => $details->key,
                    'requiresAcceptanceDefault' => $details->requiresAcceptanceDefault,
                    'isSystem' => $details->isSystem,
                    'createdAt' => $details->createdAt,
                    'updatedAt' => $details->updatedAt,
                ],
                'capabilities' => $capabilities,
            ]
        );
    }
}
