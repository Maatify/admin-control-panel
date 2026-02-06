<?php

declare(strict_types=1);

namespace Maatify\I18n\DTO;

final readonly class DomainScopeDTO
{
    public function __construct(
        public int $id,
        public string $scopeCode,
        public string $domainCode,
        public bool $isActive,
        public string $createdAt,
    ) {}
}
