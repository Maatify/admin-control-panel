<?php

declare(strict_types=1);

namespace Maatify\Catalog\Category\DTO;

use DateTimeImmutable;
use Maatify\Catalog\Category\Exception\CategoryInvalidArgumentException;

final readonly class CategoryTranslationDTO
{
    public function __construct(
        public int $id,
        public int $categoryId,
        public string $languageCode,
        public string $name,
        public ?string $description,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public ?DateTimeImmutable $deletedAt,
    ) {
        if ($id < 1) {
            throw CategoryInvalidArgumentException::nonPositiveId('id');
        }

        if ($categoryId < 1) {
            throw CategoryInvalidArgumentException::nonPositiveId('categoryId');
        }
    }

}
