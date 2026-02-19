<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-19 23:27
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\ContentDocuments;

use Maatify\AdminKernel\Domain\ContentDocuments\DTO\ContentDocumentListResponseDTO;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\AdminKernel\Infrastructure\Query\ResolvedListFilters;

interface ContentDocumentQueryReaderInterface
{
    public function query(
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ):ContentDocumentListResponseDTO;
}
