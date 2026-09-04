<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Translations\DTO;

use JsonSerializable;

/**
 * @phpstan-type I18nScopeDomainKeysSummaryListItemArray array{
 *   id: int,
 *   key_part: string,
 *   description: string|null,
 *   total_languages: int,
 *   missing_count: int
 * }
 */
final readonly class I18nScopeDomainKeysSummaryListItemDTO implements JsonSerializable
{
    public function __construct(
        public int $id,
        public string $keyPart,
        public ?string $description,
        public int $totalLanguages,
        public int $missingCount,
    ) {}

    /**
     * @return I18nScopeDomainKeysSummaryListItemArray
     */
    public function jsonSerialize(): array
    {
        return [
            'id'              => $this->id,
            'key_part'        => $this->keyPart,
            'description'     => $this->description,
            'total_languages' => $this->totalLanguages,
            'missing_count'   => $this->missingCount,
        ];
    }
}
