<?php

declare(strict_types=1);

namespace Maatify\Catalog\Category\DTO;

use DateTimeImmutable;
use JsonSerializable;
use Maatify\Catalog\Category\Exception\CategoryInvalidArgumentException;

final readonly class CategoryTranslationDTO implements JsonSerializable
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

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->categoryId,
            'language_code' => $this->languageCode,
            'name' => $this->name,
            'description' => $this->description,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deletedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
