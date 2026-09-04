<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\LanguageCore\DTO;

use JsonSerializable;

/**
 * @phpstan-type LanguageWithSettingsItemArray array{
 *     id: int,
 *     name: string,
 *     code: string,
 *     direction: 'ltr'|'rtl',
 *     icon: string|null,
 *     sort_order: int,
 *     fallback_language_id: int|null
 * }
 */
final readonly class LanguageWithSettingsItemDTO implements JsonSerializable
{
    /**
     * @param 'ltr'|'rtl' $direction
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $code,
        public string $direction,
        public ?string $icon,
        public int $sort_order,
        public ?int $fallback_language_id,
    ) {
    }

    /**
     * @return LanguageWithSettingsItemArray
     */
    public function jsonSerialize(): array
    {
        return [
            'id'                   => $this->id,
            'name'                 => $this->name,
            'code'                 => $this->code,
            'direction'            => $this->direction,
            'icon'                 => $this->icon,
            'sort_order'           => $this->sort_order,
            'fallback_language_id' => $this->fallback_language_id,
        ];
    }
}