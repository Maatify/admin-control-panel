<?php

declare(strict_types=1);

namespace App\Domain\DTO\AdminList;

use App\Domain\Admin\Enum\AdminStatusEnum;
use JsonSerializable;

readonly class AdminListItemDTO implements JsonSerializable
{
    public function __construct(
        public int $id,
        public string $displayName,
        public AdminStatusEnum $status,
        public string $createdAt
    ) {}

    /**
     * @return array{id: int, display_name: string, status: string, created_at: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'display_name' => $this->displayName,
            'status' => $this->status->value,
            'created_at' => $this->createdAt,
        ];
    }
}
