<?php

declare(strict_types=1);

namespace Maatify\I18n\DTO;

final readonly class DomainDTO
{
    public function __construct(
        public int $id,
        public string $code,
        public string $name,
        public ?string $description,
        public bool $isActive,
        public int $sortOrder,
        public string $createdAt,
    ) {}
}
