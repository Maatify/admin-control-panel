<?php

declare(strict_types=1);

namespace Maatify\I18n\DTO;

final readonly class TranslationKeyDTO
{
    public function __construct(
        public int $id,
        public string $key,
        public ?string $description,
        public string $createdAt,
    ) {}
}
