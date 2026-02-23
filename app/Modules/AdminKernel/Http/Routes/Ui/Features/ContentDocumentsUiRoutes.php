<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Routes\Ui\Features;

use Maatify\AdminKernel\Http\Middleware\AuthorizationGuardMiddleware;
use Psr\Container\ContainerInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;

final class ContentDocumentsUiRoutes
{
    /**
     * @param RouteCollectorProxyInterface<ContainerInterface> $group
     */
    public static function register(RouteCollectorProxyInterface $group): void
    {
        $group->get(
            '/content-document-types',
            [\Maatify\AdminKernel\Http\Controllers\Ui\ContentDocuments\UiContentDocumentTypesController::class, 'index']
        )
            ->setName('content_documents.types.query.ui')
            ->add(AuthorizationGuardMiddleware::class);

        $group->get(
            '/content-document-types/{type_id:[0-9]+}/documents',
            [\Maatify\AdminKernel\Http\Controllers\Ui\ContentDocuments\UiContentDocumentVersionsController::class, 'index']
        )
            ->setName('content_documents.versions.query.ui')
            ->add(AuthorizationGuardMiddleware::class);

        $group->get(
            '/content-document-types/{type_id:[0-9]+}/documents/{document_id:[0-9]+}/translations',
            [\Maatify\AdminKernel\Http\Controllers\Ui\ContentDocuments\UiContentDocumentTranslationsController::class, 'index']
        )
            ->setName('content_documents.translations.query.ui')
            ->add(AuthorizationGuardMiddleware::class);

        $group->get(
            '/content-document-types/{type_id:[0-9]+}/documents/{document_id:[0-9]+}/translations/{translation_id:(?:[0-9]+|new)}',
            [\Maatify\AdminKernel\Http\Controllers\Ui\ContentDocuments\UiContentDocumentTranslationsUpdateController::class, 'index']
        )
            ->setName('content_documents.translations.details')
            ->add(AuthorizationGuardMiddleware::class);

        $group->get(
            '/content-document-types/{type_id:[0-9]+}/documents/{document_id:[0-9]+}/acceptance',
            [\Maatify\AdminKernel\Http\Controllers\Ui\ContentDocuments\UiDocumentAcceptanceController::class, 'index']
        )
            ->setName('content_documents.acceptance.query.ui')
            ->add(AuthorizationGuardMiddleware::class);
    }
}
