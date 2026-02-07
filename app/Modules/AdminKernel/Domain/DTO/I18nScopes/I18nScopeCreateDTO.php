<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO\I18nScopes;

final readonly class I18nScopeCreateDTO
{
    public function __construct(
        public string $code,
        public string $name,
        public string $description,
        public int $is_active,
        public int $sort_order
    ) {}
}


