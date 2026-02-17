<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\ContentDocuments\DTO;

use JsonSerializable;

/**
 * @phpstan-type DocumentTypeDropdownItemArray array{
 *     key: string,
 *     label: string
 * }
 */
final class DocumentTypeDropdownItemDTO implements JsonSerializable
{
    public function __construct(
        public string $key,
        public string $label,
    ) {}

    /**
     * @return DocumentTypeDropdownItemArray
     */
    public function jsonSerialize(): array
    {
        return [
            'key'   => $this->key,
            'label' => $this->label,
        ];
    }
}
