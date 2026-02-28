<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Domain\DTO;

use JsonSerializable;

/**
 * @phpstan-type I18nDomainDetails array{
 *   id: int,
 *   code: string,
 *   name: string,
 *   description: string|null,
 *   is_active: int,
 *   sort_order: int
 * }
 */
class I18nDomainDetailsDTO implements JsonSerializable
{
    public function __construct(
        public int $id,
        public string $code,
        public string $name,
        public ?string $description,
        public int $is_active,
        public int $sort_order
    ) {
    }

    /**
     * @return I18nDomainDetails
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];
    }
}
