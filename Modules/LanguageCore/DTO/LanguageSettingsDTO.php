<?php

declare(strict_types=1);

namespace Maatify\LanguageCore\DTO;

use Maatify\LanguageCore\Enum\TextDirectionEnum;

final readonly class LanguageSettingsDTO
{
    public function __construct(
        public int $languageId,
        public TextDirectionEnum $direction,
        public ?string $icon,
        public int $sortOrder,
    )
    {
    }
}
