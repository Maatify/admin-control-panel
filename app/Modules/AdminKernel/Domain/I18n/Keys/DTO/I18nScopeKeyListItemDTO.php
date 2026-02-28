<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Keys\DTO;

/**
 * @phpstan-type I18nScopeKeyListItemArray array{
 *   id: int,
 *   scope: string,
 *   domain: string,
 *   key_part: string,
 *   description: string|null,
 *   created_at: string
 * }
 */
final readonly class I18nScopeKeyListItemDTO implements \JsonSerializable
{
    public function __construct(
        public int $id,
        public string $scope,
        public string $domain,
        public string $key_part,
        public ?string $description,
        public string $created_at
    ) {}

    /**
     * @return I18nScopeKeyListItemArray
     */
    public function jsonSerialize(): array
    {
        return [
            'id'          => $this->id,
            'scope'       => $this->scope,
            'domain'      => $this->domain,
            'key_part'    => $this->key_part,
            'description' => $this->description,
            'created_at'  => $this->created_at,
        ];
    }
}
