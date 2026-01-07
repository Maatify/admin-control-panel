<?php

declare(strict_types=1);

namespace App\Domain\DTO\Session;

class SessionListQueryDTO
{
    /**
     * @param int $page
     * @param int $per_page
     * @param array{session_id?: string|null, status?: string|null} $filters
     * @param string $current_session_id
     */
    public function __construct(
        public int $page,
        public int $per_page,
        public array $filters,
        public string $current_session_id
    ) {
    }
}
