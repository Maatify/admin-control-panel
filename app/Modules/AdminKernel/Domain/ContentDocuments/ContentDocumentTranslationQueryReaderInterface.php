<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-23 17:14
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\ContentDocuments;

use Maatify\AdminKernel\Domain\ContentDocuments\DTO\ContentDocumentTranslationListResponseDTO;
use Maatify\AdminKernel\Domain\List\Filters\ResolvedListFilters;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;

interface ContentDocumentTranslationQueryReaderInterface
{
    public function query(
        int $documentId,
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): ContentDocumentTranslationListResponseDTO;
}