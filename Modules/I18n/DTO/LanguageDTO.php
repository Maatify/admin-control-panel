<?php

declare(strict_types=1);

namespace Maatify\I18n\DTO;

final readonly class LanguageDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $code,
        public bool $isActive,
        public ?int $fallbackLanguageId,
        public string $createdAt,
        public ?string $updatedAt,
    ) {}
}
