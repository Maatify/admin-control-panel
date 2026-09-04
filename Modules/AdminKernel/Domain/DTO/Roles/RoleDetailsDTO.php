<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO\Roles;

readonly class RoleDetailsDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $display_name,
        public ?string $description,
        public int $is_active,
    ) {
    }
}
