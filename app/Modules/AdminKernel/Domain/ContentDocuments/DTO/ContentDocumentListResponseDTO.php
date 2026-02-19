<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\ContentDocuments\DTO;

use JsonSerializable;
use Maatify\AdminKernel\Domain\DTO\Common\PaginationDTO;

/**
 * @phpstan-type ContentDocumentListResponseArray array{
 *   data: ContentDocumentListItemDTO[],
 *   pagination: PaginationDTO
 * }
 */
class ContentDocumentListResponseDTO implements JsonSerializable
{

    /** @param ContentDocumentListItemDTO[] $data */
    public function __construct(
        public array $data,
        public PaginationDTO $pagination
    )
    {
    }

    /** @return ContentDocumentListResponseArray */
    public function jsonSerialize(): array
    {
        return [
            'data' => $this->data,
            'pagination' => $this->pagination,
        ];
    }
}
