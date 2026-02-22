<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-22 01:29
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Controllers\Api\ContentDocuments;

use Maatify\AdminKernel\Domain\Exception\IdentifierNotFoundException;
use Maatify\AdminKernel\Http\Response\JsonResponseFactory;
use Maatify\ContentDocuments\Domain\Contract\Service\ContentDocumentsFacadeInterface;
use Maatify\Exceptions\Exception\Validation\InvalidArgumentMaatifyException;
use Maatify\SharedCommon\Contracts\ClockInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final readonly class ContentDocumentVersionsArchiveController
{
    public function __construct(
        private ContentDocumentsFacadeInterface $facade,
        private JsonResponseFactory $json,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @param array{type_id?: string, document_id?: string} $args
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

        $versionDetails = $this->facade->getDocumentById($documentId);
        if ($versionDetails == null || $versionDetails->documentTypeId !== $typeId) {
            throw new IdentifierNotFoundException('Document not found.');
        }

        $this->facade->archive($documentId, $this->clock->now());

        // 3) Success — no payload
        return $this->json->success($response);
    }
}
