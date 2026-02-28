<?php

declare(strict_types=1);

namespace Maatify\I18n\DTO;

final readonly class TranslationUpsertResultDTO
{
    public function __construct(
        public int $id,
        public bool $created
    )
    {
    }
}
