<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\ContentDocuments\DTO;

use JsonSerializable;
use Maatify\AdminKernel\Domain\DTO\Common\PaginationDTO;

/**
 * @phpstan-type ContentDocumentVersionsListResponseArray array{
 *   data: ContentDocumentVersionsListItemDTO[],
 *   pagination: PaginationDTO
 * }
 */
final class ContentDocumentVersionsListResponseDTO implements JsonSerializable
{
    /**
     * @param ContentDocumentVersionsListItemDTO[] $data
     */
    public function __construct(
        public array $data,
        public PaginationDTO $pagination
    ) {
    }

    /**
     * @return ContentDocumentVersionsListResponseArray
     */
    public function jsonSerialize(): array
    {
        return [
            'data' => $this->data,
            'pagination' => $this->pagination,
        ];
    }
}