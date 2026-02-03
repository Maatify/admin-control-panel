<?php

declare(strict_types=1);

namespace Maatify\I18n\DTO;

final readonly class TranslationDTO
{
    public function __construct(
        public int $id,
        public int $keyId,
        public int $languageId,
        public string $value,
        public string $createdAt,
        public ?string $updatedAt,
    ) {}
}
