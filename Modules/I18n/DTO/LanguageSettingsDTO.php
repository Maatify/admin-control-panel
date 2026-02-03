<?php

declare(strict_types=1);

namespace Maatify\I18n\DTO;

use Maatify\I18n\Enum\TextDirectionEnum;

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
