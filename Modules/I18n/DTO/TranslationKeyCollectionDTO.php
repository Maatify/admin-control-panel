<?php

namespace Maatify\I18n\DTO;

/**
 * @phpstan-type TranslationKeyList list<TranslationKeyDTO>
 */
final readonly class TranslationKeyCollectionDTO
{
    /**
     * @param   list<TranslationKeyDTO>  $items
     */
    public function __construct(
        public array $items,
    )
    {
    }
}
