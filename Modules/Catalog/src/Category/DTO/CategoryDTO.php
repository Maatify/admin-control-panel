<?php

declare(strict_types=1);

namespace Maatify\Catalog\Category\DTO;

use DateTimeImmutable;
use Maatify\Catalog\Category\Enum\CategoryStatusEnum;
use Maatify\Catalog\Category\Exception\CategoryInvalidArgumentException;

final readonly class CategoryDTO
{
    public function __construct(
        public int $id,
        public ?int $parentId,
        public string $code,
        public CategoryStatusEnum $status,
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

}
