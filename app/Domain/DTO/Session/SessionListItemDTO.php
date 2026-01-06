<?php

declare(strict_types=1);

namespace App\Domain\DTO\Session;

use JsonSerializable;

class SessionListItemDTO implements JsonSerializable
{
    public function __construct(
        public string $session_id,
        public string $created_at,
        public string $expires_at,
        public string $status
    ) {
    }

    /**
     * @return array{session_id: string, created_at: string, expires_at: string, status: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'session_id' => $this->session_id,
            'created_at' => $this->created_at,
            'expires_at' => $this->expires_at,
            'status' => $this->status,
        ];
    }
}
