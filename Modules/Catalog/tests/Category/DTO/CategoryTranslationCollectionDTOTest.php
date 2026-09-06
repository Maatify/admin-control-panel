<?php

declare(strict_types=1);

namespace Maatify\Catalog\Tests\Category\DTO;

use DateTimeImmutable;
use Maatify\Catalog\Category\DTO\CategoryTranslationCollectionDTO;
use Maatify\Catalog\Category\DTO\CategoryTranslationDTO;
use PHPUnit\Framework\TestCase;

final class CategoryTranslationCollectionDTOTest extends TestCase
{
    public function testItExposesTypedTranslationsWithoutAnAssociativeArrayContract(): void
    {
        $first = $this->translation(1, 'ar-EG');
        $second = $this->translation(2, 'en-US');
        $collection = new CategoryTranslationCollectionDTO([$first, $second]);

        self::assertCount(2, $collection);
        self::assertFalse($collection->isEmpty());
        self::assertSame([$first, $second], iterator_to_array($collection));
    }

    private function translation(int $id, string $languageCode): CategoryTranslationDTO
    {
        $timestamp = new DateTimeImmutable('2026-01-01 00:00:00 UTC');

        return new CategoryTranslationDTO(
            id: $id,
            categoryId: 7,
            languageCode: $languageCode,
            name: 'Category ' . $id,
            description: null,
            createdAt: $timestamp,
            updatedAt: $timestamp,
            deletedAt: null,
        );
    }
}
