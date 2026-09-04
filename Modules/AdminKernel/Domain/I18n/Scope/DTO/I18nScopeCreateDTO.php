<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Scope\DTO;

final readonly class I18nScopeCreateDTO
{
    public function __construct(
        public string $code,
        public string $name,
        public string $description,
        public int $is_active,
    ) {}
}


