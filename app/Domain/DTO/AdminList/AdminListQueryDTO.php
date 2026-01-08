<?php

declare(strict_types=1);

namespace App\Domain\DTO\AdminList;

readonly class AdminListQueryDTO
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 10,
        public ?int $adminId = null,
        public ?string $email = null
    ) {
    }
}
