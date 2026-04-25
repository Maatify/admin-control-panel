<?php

declare(strict_types=1);

namespace Tests\Modules\Category\Unit\Service;

use Maatify\Category\Command\CreateCategoryCommand;
use Maatify\Category\Command\DeleteCategorySettingCommand;
use Maatify\Category\Command\UpdateCategoryCommand;
use Maatify\Category\Command\UpdateCategoryStatusCommand;
use Maatify\Category\Command\UpsertCategorySettingCommand;
use Maatify\Category\Contract\CategoryCommandRepositoryInterface;
use Maatify\Category\Contract\CategoryQueryReaderInterface;
use Maatify\Category\DTO\CategoryDTO;
use Maatify\Category\DTO\CategorySettingDTO;
use Maatify\Category\Exception\CategoryCircularReferenceException;
use Maatify\Category\Exception\CategoryDepthExceededException;
use Maatify\Category\Exception\CategoryInvalidArgumentException;
use Maatify\Category\Exception\CategoryNotFoundException;
use Maatify\Category\Exception\CategorySlugAlreadyExistsException;
use Maatify\Category\Service\CategoryCommandService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CategoryCommandService.
 *
 * Business rules under test:
 *   1. Slug uniqueness (TOCTOU guard)
 *   2. Max hierarchy depth = 1 (no sub-sub-categories)
 *   3. No circular references on update
 *   4. Parent must exist before being assigned
 *   5. display_order must be >= 1 in reorder()
 *   6. Category must exist for status / reorder / setting mutations
 *
 * All infrastructure is replaced by PHPUnit mocks — no database required.
 */
final class CategoryCommandServiceTest extends TestCase
{
    private MockObject&CategoryCommandRepositoryInterface $commandRepo;
    private MockObject&CategoryQueryReaderInterface $queryReader;
    private CategoryCommandService $service;

    protected function setUp(): void
    {
        $this->commandRepo = $this->createMock(CategoryCommandRepositoryInterface::class);
        $this->queryReader = $this->createMock(CategoryQueryReaderInterface::class);
        $this->service     = new CategoryCommandService($this->commandRepo, $this->queryReader);
    }

    // ------------------------------------------------------------------ //
    //  Helpers
    // ------------------------------------------------------------------ //

    private function makeDto(int $id, ?int $parentId = null): CategoryDTO
    {
        return new CategoryDTO(
            id:           $id,
            parentId:     $parentId,
            name:         'Test Category',
            slug:         'test-category',
            isActive:     true,
            displayOrder: 1,
            createdAt:    '2026-01-01 00:00:00',
            updatedAt:    null,
            childCount:   0,
        );
    }

    private function makeSettingDto(int $categoryId = 5): CategorySettingDTO
    {
        return new CategorySettingDTO(
            id:         1,
            categoryId: $categoryId,
            key:        'color',
            value:      'blue',
            createdAt:  '2026-01-01 00:00:00',
            updatedAt:  null,
        );
    }

    // ================================================================== //
    //  create()
    // ================================================================== //

    public function testCreateRootCategorySucceeds(): void
    {
        $command = new CreateCategoryCommand(name: 'Electronics', slug: 'electronics');
        $dto     = $this->makeDto(1);

        $this->queryReader->expects($this->once())
            ->method('findBySlug')
            ->with('electronics')
            ->willReturn(null);

        $this->commandRepo->expects($this->once())
            ->method('create')
            ->with($command)
            ->willReturn($dto);

        $result = $this->service->create($command);

        $this->assertSame($dto, $result);
    }

    public function testCreateSubCategorySucceeds(): void
    {
        $command   = new CreateCategoryCommand(name: 'Phones', slug: 'phones', parentId: 1);
        $parentDto = $this->makeDto(1);          // root (parentId = null)
        $newDto    = $this->makeDto(2, parentId: 1);

        $this->queryReader->method('findBySlug')->with('phones')->willReturn(null);
        $this->queryReader->method('findById')->with(1)->willReturn($parentDto);

        $this->commandRepo->expects($this->once())
            ->method('create')
            ->willReturn($newDto);

        $result = $this->service->create($command);

        $this->assertSame($newDto, $result);
    }

    public function testCreateThrowsSlugAlreadyExistsWhenSlugIsTaken(): void
    {
        $command  = new CreateCategoryCommand(name: 'New', slug: 'electronics');
        $conflict = $this->makeDto(99); // different row already owns this slug

        $this->queryReader->expects($this->once())
            ->method('findBySlug')
            ->with('electronics')
            ->willReturn($conflict);

        $this->expectException(CategorySlugAlreadyExistsException::class);

        $this->service->create($command);
    }

    public function testCreateThrowsNotFoundWhenParentDoesNotExist(): void
    {
        $command = new CreateCategoryCommand(name: 'Phones', slug: 'phones', parentId: 99);

        $this->queryReader->method('findBySlug')->willReturn(null);
        $this->queryReader->method('findById')->with(99)->willReturn(null);

        $this->expectException(CategoryNotFoundException::class);

        $this->service->create($command);
    }

    public function testCreateThrowsDepthExceededWhenParentIsSubCategory(): void
    {
        // The proposed parent (id=2) is already a sub-category (parentId=1).
        // Adding a child to it would create depth 2 — not allowed.
        $command   = new CreateCategoryCommand(name: 'Accessories', slug: 'accessories', parentId: 2);
        $subParent = $this->makeDto(2, parentId: 1);

        $this->queryReader->method('findBySlug')->willReturn(null);
        $this->queryReader->method('findById')->with(2)->willReturn($subParent);

        $this->expectException(CategoryDepthExceededException::class);

        $this->service->create($command);
    }

    // ================================================================== //
    //  update()
    // ================================================================== //

    public function testUpdateSucceeds(): void
    {
        $command = new UpdateCategoryCommand(
            id:           5,
            name:         'Updated Name',
            slug:         'updated-slug',
            parentId:     null,
            isActive:     true,
            displayOrder: 2,
        );
        $dto = $this->makeDto(5);

        $this->queryReader->method('findById')->with(5)->willReturn($dto);
        $this->queryReader->method('findBySlug')->with('updated-slug')->willReturn(null);

        $this->commandRepo->expects($this->once())
            ->method('update')
            ->with($command)
            ->willReturn($dto);

        $result = $this->service->update($command);

        $this->assertSame($dto, $result);
    }

    public function testUpdateThrowsNotFoundWhenCategoryDoesNotExist(): void
    {
        $command = new UpdateCategoryCommand(
            id:           999,
            name:         'Ghost',
            slug:         'ghost',
            parentId:     null,
            isActive:     true,
            displayOrder: 1,
        );

        $this->queryReader->method('findById')->with(999)->willReturn(null);

        $this->expectException(CategoryNotFoundException::class);

        $this->service->update($command);
    }

    public function testUpdateAllowsRetainingOwnSlug(): void
    {
        // 'my-slug' belongs to id=5. Updating id=5 with the same slug must NOT throw.
        $command  = new UpdateCategoryCommand(
            id:           5,
            name:         'Same Name',
            slug:         'my-slug',
            parentId:     null,
            isActive:     true,
            displayOrder: 1,
        );
        $existing = new CategoryDTO(5, null, 'Same Name', 'my-slug', true, 1, '2026-01-01', null, 0);

        $this->queryReader->method('findById')->with(5)->willReturn($existing);
        $this->queryReader->method('findBySlug')->with('my-slug')->willReturn($existing);

        $this->commandRepo->expects($this->once())
            ->method('update')
            ->willReturn($existing);

        $result = $this->service->update($command);

        $this->assertSame($existing, $result);
    }

    public function testUpdateThrowsSlugAlreadyExistsWhenSlugOwnedByDifferentRow(): void
    {
        $command = new UpdateCategoryCommand(
            id:           5,
            name:         'Electronics',
            slug:         'taken-slug',
            parentId:     null,
            isActive:     true,
            displayOrder: 1,
        );
        $selfDto     = $this->makeDto(5);
        $conflictDto = $this->makeDto(99); // different row owns 'taken-slug'

        $this->queryReader->method('findById')->with(5)->willReturn($selfDto);
        $this->queryReader->method('findBySlug')->with('taken-slug')->willReturn($conflictDto);

        $this->expectException(CategorySlugAlreadyExistsException::class);

        $this->service->update($command);
    }

    public function testUpdateThrowsCircularReferenceWhenCategorySetAsOwnParent(): void
    {
        // Assigning a category as its own parent is the only possible circular reference
        // at max depth = 1. assertNotCircularReference() catches this.
        $command = new UpdateCategoryCommand(
            id:           7,
            name:         'Test',
            slug:         'test',
            parentId:     7, // same as id
            isActive:     true,
            displayOrder: 1,
        );
        // id=7 exists and is a root (parentId=null) so assertParentExistsAndIsRoot passes.
        $dto = $this->makeDto(7);

        $this->queryReader->method('findById')->willReturn($dto);
        $this->queryReader->method('findBySlug')->willReturn(null);

        $this->expectException(CategoryCircularReferenceException::class);

        $this->service->update($command);
    }

    public function testUpdateThrowsDepthExceededWhenNewParentIsSubCategory(): void
    {
        // Moving category id=3 under parent id=2, but id=2 is itself a sub-category.
        $command   = new UpdateCategoryCommand(
            id:           3,
            name:         'Test',
            slug:         'test',
            parentId:     2,
            isActive:     true,
            displayOrder: 1,
        );
        $selfDto   = $this->makeDto(3);
        $subParent = $this->makeDto(2, parentId: 1); // id=2 is a sub-category

        $this->queryReader->method('findBySlug')->willReturn(null);
        $this->queryReader
            ->method('findById')
            ->willReturnMap([
                [3, $selfDto],
                [2, $subParent],
            ]);

        $this->expectException(CategoryDepthExceededException::class);

        $this->service->update($command);
    }

    // ================================================================== //
    //  updateStatus()
    // ================================================================== //

    public function testUpdateStatusSucceeds(): void
    {
        $command = new UpdateCategoryStatusCommand(id: 5, isActive: false);
        $dto     = $this->makeDto(5);

        $this->queryReader->expects($this->once())
            ->method('findById')
            ->with(5)
            ->willReturn($dto);

        $this->commandRepo->expects($this->once())
            ->method('updateStatus')
            ->with($command)
            ->willReturn($dto);

        $result = $this->service->updateStatus($command);

        $this->assertSame($dto, $result);
    }

    public function testUpdateStatusThrowsNotFoundWhenCategoryDoesNotExist(): void
    {
        $command = new UpdateCategoryStatusCommand(id: 404, isActive: true);

        $this->queryReader->method('findById')->with(404)->willReturn(null);

        $this->expectException(CategoryNotFoundException::class);

        $this->service->updateStatus($command);
    }

    // ================================================================== //
    //  reorder()
    // ================================================================== //

    public function testReorderSucceeds(): void
    {
        $dto = $this->makeDto(5);

        $this->queryReader->expects($this->once())
            ->method('findById')
            ->with(5)
            ->willReturn($dto);

        $this->commandRepo->expects($this->once())
            ->method('reorder')
            ->with(5, 3, null);

        $this->service->reorder(id: 5, newOrder: 3, parentId: null);
    }

    public function testReorderSucceedsWithParentScope(): void
    {
        $dto = $this->makeDto(10, parentId: 2);

        $this->queryReader->expects($this->once())
            ->method('findById')
            ->with(10)
            ->willReturn($dto);

        $this->commandRepo->expects($this->once())
            ->method('reorder')
            ->with(10, 2, 2);

        $this->service->reorder(id: 10, newOrder: 2, parentId: 2);
    }

    public function testReorderThrowsInvalidArgumentWhenOrderIsZero(): void
    {
        $this->expectException(CategoryInvalidArgumentException::class);
        $this->expectExceptionMessage('display_order must be >= 1, got 0');

        // Guard fires BEFORE assertExists — no DB call expected.
        $this->queryReader->expects($this->never())->method('findById');

        $this->service->reorder(id: 5, newOrder: 0, parentId: null);
    }

    public function testReorderThrowsInvalidArgumentWhenOrderIsNegative(): void
    {
        $this->expectException(CategoryInvalidArgumentException::class);

        $this->service->reorder(id: 5, newOrder: -3, parentId: null);
    }

    public function testReorderThrowsNotFoundWhenCategoryDoesNotExist(): void
    {
        $this->queryReader->method('findById')->with(999)->willReturn(null);

        $this->expectException(CategoryNotFoundException::class);

        $this->service->reorder(id: 999, newOrder: 1, parentId: null);
    }

    // ================================================================== //
    //  upsertSetting()
    // ================================================================== //

    public function testUpsertSettingSucceeds(): void
    {
        $command     = new UpsertCategorySettingCommand(categoryId: 5, key: 'color', value: 'blue');
        $categoryDto = $this->makeDto(5);
        $settingDto  = $this->makeSettingDto();

        $this->queryReader->expects($this->once())
            ->method('findById')
            ->with(5)
            ->willReturn($categoryDto);

        $this->commandRepo->expects($this->once())
            ->method('upsertSetting')
            ->with($command)
            ->willReturn($settingDto);

        $result = $this->service->upsertSetting($command);

        $this->assertSame($settingDto, $result);
    }

    public function testUpsertSettingThrowsNotFoundWhenCategoryDoesNotExist(): void
    {
        $command = new UpsertCategorySettingCommand(categoryId: 999, key: 'color', value: 'red');

        $this->queryReader->method('findById')->with(999)->willReturn(null);

        $this->expectException(CategoryNotFoundException::class);

        $this->service->upsertSetting($command);
    }

    // ================================================================== //
    //  deleteSetting()
    // ================================================================== //

    public function testDeleteSettingSucceeds(): void
    {
        $command     = new DeleteCategorySettingCommand(categoryId: 5, key: 'color');
        $categoryDto = $this->makeDto(5);

        $this->queryReader->expects($this->once())
            ->method('findById')
            ->with(5)
            ->willReturn($categoryDto);

        $this->commandRepo->expects($this->once())
            ->method('deleteSetting')
            ->with($command);

        $this->service->deleteSetting($command);
        $this->addToAssertionCount(1); // explicit void success
    }

    public function testDeleteSettingThrowsNotFoundWhenCategoryDoesNotExist(): void
    {
        $command = new DeleteCategorySettingCommand(categoryId: 999, key: 'color');

        $this->queryReader->method('findById')->with(999)->willReturn(null);

        $this->expectException(CategoryNotFoundException::class);

        $this->service->deleteSetting($command);
    }
}



