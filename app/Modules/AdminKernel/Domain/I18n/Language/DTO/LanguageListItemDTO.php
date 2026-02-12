<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Language\DTO;

use JsonSerializable;
use Maatify\LanguageCore\Enum\TextDirectionEnum;

/**
 * @phpstan-type LanguageListItemArray array{
 *   id: int,
 *   name: string,
 *   code: string,
 *   is_active: bool,
 *   fallback_language_id: int|null,
 *   direction: string,
 *   icon: string|null,
 *   sort_order: int,
 *   created_at: string,
 *   updated_at: string|null
 * }
 */
final readonly class LanguageListItemDTO implements JsonSerializable
{
    public function __construct(
        public int $id,
        public string $name,
        public string $code,
        public bool $isActive,
        public ?int $fallbackLanguageId,
        public TextDirectionEnum $direction,
        public ?string $icon,
        public int $sortOrder,
        public string $createdAt,
        public ?string $updatedAt,
    ) {}

    /**
     * @return LanguageListItemArray
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'is_active' => $this->isActive,
            'fallback_language_id' => $this->fallbackLanguageId,
            'direction' => $this->direction->value,
            'icon' => $this->icon,
            'sort_order' => $this->sortOrder,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
