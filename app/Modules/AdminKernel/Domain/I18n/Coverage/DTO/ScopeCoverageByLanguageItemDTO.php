<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Coverage\DTO;

use JsonSerializable;

/**
 * @phpstan-type ScopeCoverageByLanguageItemArray array{
 *     language_id: int,
 *     language_code: string,
 *     language_name: string,
 *     language_icon: string|null,
 *     total_keys: int,
 *     translated_count: int,
 *     missing_count: int,
 *     completion_percent: float
 * }
 */
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

    /**
     * @return ScopeCoverageByLanguageItemArray
     */
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
