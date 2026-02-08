<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO\I18n\Domains;

use JsonSerializable;

/**
 * @phpstan-type I18nDomainListItemArray array{
 *   id: int,
 *   code: string,
 *   name: string,
 *   description: string,
 *   is_active: int,
 *   sort_order: int
 * }
 */
final readonly class I18nDomainsListItemDTO implements JsonSerializable
{
    public function __construct(
        public int $id,
        public string $code,
        public string $name,
        public string $description,
        public int $is_active,
        public int $sort_order
    ) {
    }

    /**
     * @return I18nDomainListItemArray
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
