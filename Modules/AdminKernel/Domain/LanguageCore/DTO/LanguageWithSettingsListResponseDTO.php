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
 *
 * @phpstan-type LanguageWithSettingsListArray array{
 *     items: list<LanguageWithSettingsItemArray>
 * }
 */
final readonly class LanguageWithSettingsListResponseDTO implements JsonSerializable
{
    /**
     * @param list<LanguageWithSettingsItemDTO> $items
     */
    public function __construct(
        public array $items
    ) {
    }

    /**
     * @return LanguageWithSettingsListArray
     */
    public function jsonSerialize(): array
    {
        return [
            'items' => array_map(
                static fn (LanguageWithSettingsItemDTO $item): array => $item->jsonSerialize(),
                $this->items
            ),
        ];
    }
}