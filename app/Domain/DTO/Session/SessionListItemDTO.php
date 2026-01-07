<?php

declare(strict_types=1);

namespace App\Domain\DTO\Session;

use JsonSerializable;

class SessionListItemDTO implements JsonSerializable
{
    public function __construct(
        public string $session_id,
        public int $admin_id,
        public string $admin_identifier,
        public string $created_at,
        public string $expires_at,
        public string $status,
        public bool $is_current
    ) {
    }

    /**
     * @return array{session_id: string, admin_id: int, admin_identifier: string, created_at: string, expires_at: string, status: string, is_current: bool}
     */
    public function jsonSerialize(): array
    {
        return [
            'session_id' => $this->session_id,
            'admin_id' => $this->admin_id,
            'admin_identifier' => $this->admin_identifier,
            'created_at' => $this->created_at,
            'expires_at' => $this->expires_at,
            'status' => $this->status,
            'is_current' => $this->is_current,
        ];
    }
}
