<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\ContentDocuments\DTO;

use JsonSerializable;

/**
 * @phpstan-type DocumentTypeDropdownResponseArray array{
 *     data: DocumentTypeDropdownItemDTO[]
 * }
 */
final class DocumentTypeDropdownResponseDTO implements JsonSerializable
{
    /**
     * @param DocumentTypeDropdownItemDTO[] $data
     */
    public function __construct(
        public array $data,
    ) {}

    /**
     * @return DocumentTypeDropdownResponseArray
     */
    public function jsonSerialize(): array
    {
        return [
            'data' => $this->data,
        ];
    }
}
