<?php

declare(strict_types=1);

namespace Maatify\I18n\DTO;

/**
 * @phpstan-type TranslationList list<TranslationDTO>
 */
final readonly class TranslationCollectionDTO
{
    /**
     * @param   list<TranslationDTO>  $items
     */
    public function __construct(
        public array $items,
    )
    {
    }
}
