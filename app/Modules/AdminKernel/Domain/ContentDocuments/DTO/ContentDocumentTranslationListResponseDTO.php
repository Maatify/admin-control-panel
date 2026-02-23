<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\ContentDocuments\DTO;

use JsonSerializable;
use Maatify\AdminKernel\Domain\DTO\Common\PaginationDTO;

/**
 * @phpstan-type ContentDocumentTranslationListResponseArray array{
 *   data: ContentDocumentTranslationListItemDTO[],
 *   pagination: PaginationDTO
 * }
 */
final class ContentDocumentTranslationListResponseDTO implements JsonSerializable
{
    /** @param ContentDocumentTranslationListItemDTO[] $data */
    public function __construct(
        public array $data,
        public PaginationDTO $pagination
    )
    {
    }

    /** @return ContentDocumentTranslationListResponseArray */
    public function jsonSerialize(): array
    {
        return [
            'data' => $this->data,
            'pagination' => $this->pagination,
        ];
    }
}
