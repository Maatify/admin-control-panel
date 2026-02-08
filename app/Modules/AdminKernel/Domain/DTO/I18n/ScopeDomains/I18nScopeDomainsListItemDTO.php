<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO\I18n\ScopeDomains;

use JsonSerializable;

/**
 * @phpstan-type I18nScopeDomainItemArray array{
 *   id:int,
 *   code:string,
 *   name:string,
 *   description:string,
 *   is_active:int,
 *   sort_order:int,
 *   assigned:int
 * }
 */
final readonly class I18nScopeDomainsListItemDTO implements JsonSerializable
{
    public function __construct(
        public int $id,
        public string $code,
        public string $name,
        public string $description,
        public int $is_active,
        public int $sort_order,
        public int $assigned
    )
    {
    }

    /**
     * @return I18nScopeDomainItemArray
     */
    public function jsonSerialize(): array
    {
        return [
            'id'          => $this->id,
            'code'        => $this->code,
            'name'        => $this->name,
            'description' => $this->description,
            'is_active'   => $this->is_active,
            'sort_order'  => $this->sort_order,
            'assigned'    => $this->assigned,
        ];
    }
}
