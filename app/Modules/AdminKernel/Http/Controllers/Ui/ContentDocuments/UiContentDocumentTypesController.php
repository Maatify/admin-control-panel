<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-20 05:09
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Ui\ContentDocuments;

use Maatify\AdminKernel\Context\AdminContext;
use Maatify\AdminKernel\Domain\Service\AuthorizationService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

final readonly class UiContentDocumentTypesController
{
    public function __construct(
        private Twig $twig,
        private AuthorizationService $authorization
    )
    {
    }

    public function index(Request $request, Response $response): Response
    {
        /** @var AdminContext $context */
        $context = $request->getAttribute(AdminContext::class);
        $adminId = $context->adminId;

        $capabilities = [
            'can_create' => $this->authorization->hasPermission($adminId, 'content_documents.types.create'),
            'can_update' => $this->authorization->hasPermission($adminId, 'content_documents.types.update'),
            'can_view_versions' => $this->authorization->hasPermission($adminId, 'content_documents.versions.query'),
        ];

        return $this->twig->render($response, 'pages/content-documents/content_document_types.list.twig', [
            'capabilities' => $capabilities,
        ]);
    }
}
