<?php

declare(strict_types=1);

namespace Maatify\Catalog\Category\DTO;

use DateTimeImmutable;
use JsonSerializable;
use Maatify\Catalog\Category\Enum\CatalogStatusEnum;
use Maatify\Catalog\Category\Exception\CategoryInvalidArgumentException;

final readonly class CategoryDTO implements JsonSerializable
{
    public function __construct(
        public int $id,
        public ?int $parentId,
        public string $code,
        public CatalogStatusEnum $status,
        public int $displayOrder,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public ?DateTimeImmutable $deletedAt,
    ) {
        if ($id < 1) {
            throw CategoryInvalidArgumentException::nonPositiveId('id');
        }

        if ($parentId !== null && $parentId < 1) {
            throw CategoryInvalidArgumentException::nonPositiveId('parentId');
        }

        if ($parentId === $id) {
            throw CategoryInvalidArgumentException::selfParent($id);
        }
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parentId,
            'code' => $this->code,
            'status' => $this->status->value,
            'display_order' => $this->displayOrder,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deletedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
