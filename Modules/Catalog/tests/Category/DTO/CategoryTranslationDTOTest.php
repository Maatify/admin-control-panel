<?php

declare(strict_types=1);

namespace Maatify\Catalog\Tests\Category\DTO;

use DateTimeImmutable;
use Maatify\Catalog\Category\DTO\CategoryTranslationDTO;
use Maatify\Catalog\Category\Exception\CategoryInvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CategoryTranslationDTOTest extends TestCase
{
    public function testItRepresentsTranslationIdentityAndFields(): void
    {
        $translation = new CategoryTranslationDTO(
            id: 11,
            categoryId: 7,
            languageCode: 'ar-EG',
            name: 'قمصان',
            description: 'وصف الفئة',
            createdAt: new DateTimeImmutable('2026-01-01 00:00:00 UTC'),
            updatedAt: new DateTimeImmutable('2026-01-02 00:00:00 UTC'),
            deletedAt: null,
        );

        self::assertSame(11, $translation->id);
        self::assertSame(7, $translation->categoryId);
        self::assertSame('ar-EG', $translation->languageCode);
        self::assertSame('قمصان', $translation->name);
        self::assertSame('وصف الفئة', $translation->description);
        self::assertNull($translation->deletedAt);
    }

    public function testItSerializesUsingSchemaFieldNames(): void
    {
        $translation = new CategoryTranslationDTO(
            id: 11,
            categoryId: 7,
            languageCode: 'en-US',
            name: 'Shirts',
            description: null,
            createdAt: new DateTimeImmutable('2026-01-01 00:00:00 UTC'),
            updatedAt: new DateTimeImmutable('2026-01-01 00:00:00 UTC'),
            deletedAt: null,
        );

        self::assertSame([
            'id' => 11,
            'category_id' => 7,
            'language_code' => 'en-US',
            'name' => 'Shirts',
            'description' => null,
            'created_at' => '2026-01-01 00:00:00',
            'updated_at' => '2026-01-01 00:00:00',
            'deleted_at' => null,
        ], $translation->jsonSerialize());
    }

    public function testItRejectsANonPositiveCategoryId(): void
    {
        $this->expectException(CategoryInvalidArgumentException::class);

        new CategoryTranslationDTO(
            id: 11,
            categoryId: 0,
            languageCode: 'en',
            name: 'Shirts',
            description: null,
            createdAt: new DateTimeImmutable('2026-01-01 00:00:00 UTC'),
            updatedAt: new DateTimeImmutable('2026-01-01 00:00:00 UTC'),
            deletedAt: null,
        );
    }
}
