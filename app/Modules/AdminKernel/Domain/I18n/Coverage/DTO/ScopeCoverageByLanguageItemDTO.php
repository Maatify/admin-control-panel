<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Coverage\DTO;

use JsonSerializable;

final readonly class ScopeCoverageByLanguageItemDTO implements JsonSerializable
{
    public function __construct(
        public int $languageId,
        public string $languageCode,
        public string $languageName,
        public ?string $languageIcon,
        public int $totalKeys,
        public int $translatedCount,
        public int $missingCount,
        public float $completionPercent
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'language_id'        => $this->languageId,
            'language_code'      => $this->languageCode,
            'language_name'      => $this->languageName,
            'language_icon'      => $this->languageIcon,
            'total_keys'         => $this->totalKeys,
            'translated_count'   => $this->translatedCount,
            'missing_count'      => $this->missingCount,
            'completion_percent' => $this->completionPercent,
        ];
    }
}
