<?php

declare(strict_types=1);

namespace App\Domain\DTO\AdminList;

use JsonSerializable;

readonly class AdminListItemDTO implements JsonSerializable
{
    public function __construct(
        public int $id,
        public string $email,
        public string $createdAt
    ) {
    }

    /**
     * @return array{id: int, email: string, created_at: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'created_at' => $this->createdAt,
        ];
    }
}
