<?php

declare(strict_types=1);

namespace App\Domain\DTO\Session;

class SessionListQueryDTO
{
    /**
     * @param int $page
     * @param int $per_page
     * @param array{session_id?: string|null, status?: string|null} $filters
     */
    public function __construct(
        public int $page,
        public int $per_page,
        public array $filters
    ) {
    }
}
