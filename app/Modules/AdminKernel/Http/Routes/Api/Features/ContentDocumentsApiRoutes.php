<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Routes\Api\Features;

use Psr\Container\ContainerInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;

class ContentDocumentsApiRoutes
{
    /**
     * @param RouteCollectorProxyInterface<ContainerInterface> $group
     */
    public static function register(RouteCollectorProxyInterface $group): void
    {
        // ─────────────────────────────
        // Content Documents Control
        // ─────────────────────────────
        $group->group('/content-document-types', function (RouteCollectorProxyInterface $documents) {
            // Dropdown (available enum keys)
            $documents->get(
                '/dropdown',
                \Maatify\AdminKernel\Http\Controllers\Api\ContentDocuments\ContentDocumentsKeysDropdownController::class
            )->setName('content_documents.types.dropdown.api');

            // Query
            $documents->post(
                '/query',
                \Maatify\AdminKernel\Http\Controllers\Api\ContentDocuments\ContentDocumentTypesQueryController::class
            )->setName('content_documents.types.query.api');

            // Create
            $documents->post(
                '/create',
                \Maatify\AdminKernel\Http\Controllers\Api\ContentDocuments\ContentDocumentTypesCreateController::class
            )->setName('content_documents.types.create.api');

            // Update
            $documents->post(
                '/{type_id:[0-9]+}/update',
                \Maatify\AdminKernel\Http\Controllers\Api\ContentDocuments\DocumentTypeUpdateController::class
            )->setName('content_documents.types.update.api');

            $documents->group(
                '/{type_id:[0-9]+}/documents',
                function (RouteCollectorProxyInterface $versions) {

                    $versions->post(
                        '/query',
                        \Maatify\AdminKernel\Http\Controllers\Api\ContentDocuments\DocumentVersionsQueryController::class
                    )->setName('content_documents.versions.query.api');

                    $versions->post(
                        '/create',
                        \Maatify\AdminKernel\Http\Controllers\Api\ContentDocuments\DocumentVersionCreateController::class
                    )->setName('content_documents.versions.create.api');

                    $versions->group(
                        '/{document_id:[0-9]+}',
                        function (RouteCollectorProxyInterface $document) {

                            $document->post(
                                '/activate',
                                \Maatify\AdminKernel\Http\Controllers\Api\ContentDocuments\DocumentActivateController::class
                            )->setName('content_documents.versions.activate.api');

                            $document->post(
                                '/archive',
                                \Maatify\AdminKernel\Http\Controllers\Api\ContentDocuments\DocumentArchiveController::class
                            )->setName('content_documents.versions.archive.api');

                            $document->group(
                                '/translations',
                                function (RouteCollectorProxyInterface $translations) {

                                    $translations->post(
                                        '/query',
                                        \Maatify\AdminKernel\Http\Controllers\Api\ContentDocuments\DocumentTranslationsQueryController::class
                                    )->setName('content_documents.translations.query.api');

                                    $translations->post(
                                        '/upsert',
                                        \Maatify\AdminKernel\Http\Controllers\Api\ContentDocuments\DocumentTranslationUpsertController::class
                                    )->setName('content_documents.translations.upsert.api');
                                }
                            );

                            $document->group(
                                '/acceptance',
                                function (RouteCollectorProxyInterface $acceptance) {

                                    $acceptance->post(
                                        '/query',
                                        \Maatify\AdminKernel\Http\Controllers\Api\ContentDocuments\DocumentAcceptanceQueryController::class
                                    )->setName('content_documents.acceptance.query.api');
                                }
                            );
                        }
                    );

                }
            );
        });
    }
}
