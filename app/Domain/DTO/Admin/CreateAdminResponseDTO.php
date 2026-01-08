<?php

declare(strict_types=1);

namespace App\Domain\DTO\Admin;

use JsonSerializable;

readonly class CreateAdminResponseDTO implements JsonSerializable
{
    public function __construct(
        public int $id,
        public string $createdAt
    ) {
    }

    /**
     * @return array{id: int, created_at: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'created_at' => $this->createdAt,
        ];
    }
}
