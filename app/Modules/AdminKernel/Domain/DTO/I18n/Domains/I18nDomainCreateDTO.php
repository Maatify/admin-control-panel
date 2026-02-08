<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO\I18n\Domains;

final readonly class I18nDomainCreateDTO
{
    public function __construct(
        public string $code,
        public string $name,
        public string $description,
        public int $is_active,
    ) {}
}
