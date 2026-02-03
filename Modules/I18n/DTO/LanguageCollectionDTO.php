<?php

declare(strict_types=1);

namespace Maatify\I18n\DTO;

/**
 * @phpstan-type LanguageList list<LanguageDTO>
 */
final readonly class LanguageCollectionDTO
{
    /**
     * @param list<LanguageDTO> $items
     */
    public function __construct(
        public array $items,
    ) {}
}
