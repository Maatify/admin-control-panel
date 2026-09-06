<?php

declare(strict_types=1);

namespace Maatify\Catalog\Tests\Category\DTO;

use DateTimeImmutable;
use Maatify\Catalog\Category\DTO\CategoryDTO;
use Maatify\Catalog\Category\Enum\CategoryStatusEnum;
use Maatify\Catalog\Category\Exception\CategoryInvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CategoryDTOTest extends TestCase
{
    public function testItRepresentsCategoryIdentityAndLifecycleFields(): void
    {
        $createdAt = new DateTimeImmutable('2026-01-01 00:00:00 UTC');
        $updatedAt = new DateTimeImmutable('2026-01-02 00:00:00 UTC');
        $deletedAt = new DateTimeImmutable('2026-01-03 00:00:00 UTC');

        $category = new CategoryDTO(
            id: 7,
            parentId: 3,
            code: 'shirts',
            status: CategoryStatusEnum::INACTIVE,
            displayOrder: 4,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
            deletedAt: $deletedAt,
        );

        self::assertSame(7, $category->id);
        self::assertSame(3, $category->parentId);
        self::assertSame('shirts', $category->code);
        self::assertSame(CategoryStatusEnum::INACTIVE, $category->status);
        self::assertSame(4, $category->displayOrder);
        self::assertSame($createdAt, $category->createdAt);
        self::assertSame($updatedAt, $category->updatedAt);
        self::assertSame($deletedAt, $category->deletedAt);
    }

    public function testItRejectsANonPositiveCategoryId(): void
    {
        $this->expectException(CategoryInvalidArgumentException::class);

        new CategoryDTO(
            id: 0,
            parentId: null,
            code: 'root',
            status: CategoryStatusEnum::ACTIVE,
            displayOrder: 0,
            createdAt: new DateTimeImmutable('2026-01-01 00:00:00 UTC'),
            updatedAt: new DateTimeImmutable('2026-01-01 00:00:00 UTC'),
            deletedAt: null,
        );
    }

    public function testItRejectsACategoryAsItsOwnParent(): void
    {
        $this->expectException(CategoryInvalidArgumentException::class);

        new CategoryDTO(
            id: 7,
            parentId: 7,
            code: 'root',
            status: CategoryStatusEnum::ACTIVE,
            displayOrder: 0,
            createdAt: new DateTimeImmutable('2026-01-01 00:00:00 UTC'),
            updatedAt: new DateTimeImmutable('2026-01-01 00:00:00 UTC'),
            deletedAt: null,
        );
    }
}
