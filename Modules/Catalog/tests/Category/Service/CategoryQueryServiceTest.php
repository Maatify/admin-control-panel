<?php

declare(strict_types=1);

namespace Maatify\Catalog\Tests\Category\Service;

use DateTimeImmutable;
use Maatify\Catalog\Category\Contract\CategoryReadQueryInterface;
use Maatify\Catalog\Category\DTO\CategoryCollectionDTO;
use Maatify\Catalog\Category\DTO\CategoryDTO;
use Maatify\Catalog\Category\DTO\CategoryTranslationCollectionDTO;
use Maatify\Catalog\Category\DTO\CategoryTranslationDTO;
use Maatify\Catalog\Category\Enum\CategoryStatusEnum;
use Maatify\Catalog\Category\Exception\CategoryInvalidArgumentException;
use Maatify\Catalog\Category\Exception\CategoryNotFoundException;
use Maatify\Catalog\Category\Service\CategoryQueryService;
use PHPUnit\Framework\TestCase;

final class CategoryQueryServiceTest extends TestCase
{
    public function testGetByIdReturnsOnlyTheVisibleCategoryFromTheReader(): void
    {
        $category = $this->category(7);
        $reader = $this->createMock(CategoryReadQueryInterface::class);
        $reader->expects(self::once())
            ->method('findVisibleById')
            ->with(7)
            ->willReturn($category);

        $result = (new CategoryQueryService($reader))->getById(7);

        self::assertSame($category, $result);
    }

    public function testGetByIdRejectsAnUnavailableCategory(): void
    {
        $reader = $this->createMock(CategoryReadQueryInterface::class);
        $reader->method('findVisibleById')->willReturn(null);

        $this->expectException(CategoryNotFoundException::class);

        (new CategoryQueryService($reader))->getById(7);
    }

    public function testGetByIdRejectsANonPositiveIdentity(): void
    {
        $this->expectException(CategoryInvalidArgumentException::class);

        (new CategoryQueryService($this->createMock(CategoryReadQueryInterface::class)))->getById(0);
    }

    public function testListOperationsReturnTypedCollectionsFromTheReader(): void
    {
        $categories = new CategoryCollectionDTO([$this->category(1)]);
        $translations = new CategoryTranslationCollectionDTO([$this->translation(2)]);
        $reader = $this->createMock(CategoryReadQueryInterface::class);
        $reader->expects(self::once())->method('listVisibleRootCategories')->willReturn($categories);
        $reader->expects(self::once())->method('listVisibleChildren')->with(1)->willReturn($categories);
        $reader->expects(self::once())->method('listVisibleTranslations')->with(1)->willReturn($translations);
        $service = new CategoryQueryService($reader);

        self::assertSame($categories, $service->listRootCategories());
        self::assertSame($categories, $service->listChildren(1));
        self::assertSame($translations, $service->listTranslations(1));
    }

    private function category(int $id): CategoryDTO
    {
        $timestamp = new DateTimeImmutable('2026-01-01 00:00:00 UTC');

        return new CategoryDTO(
            id: $id,
            parentId: null,
            code: 'category-' . $id,
            status: CategoryStatusEnum::ACTIVE,
            displayOrder: 1,
            createdAt: $timestamp,
            updatedAt: $timestamp,
            deletedAt: null,
        );
    }

    private function translation(int $id): CategoryTranslationDTO
    {
        $timestamp = new DateTimeImmutable('2026-01-01 00:00:00 UTC');

        return new CategoryTranslationDTO(
            id: $id,
            categoryId: 1,
            languageCode: 'en-US',
            name: 'Category',
            description: null,
            createdAt: $timestamp,
            updatedAt: $timestamp,
            deletedAt: null,
        );
    }
}
