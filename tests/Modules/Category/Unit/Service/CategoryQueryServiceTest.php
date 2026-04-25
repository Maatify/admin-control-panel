<?php

declare(strict_types=1);

namespace Tests\Modules\Category\Unit\Service;

use Maatify\Category\Contract\CategoryQueryReaderInterface;
use Maatify\Category\DTO\CategoryDTO;
use Maatify\Category\DTO\CategorySettingDTO;
use Maatify\Category\Exception\CategoryNotFoundException;
use Maatify\Category\Service\CategoryQueryService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CategoryQueryService.
 *
 * Responsibilities under test:
 *   1. getById() and getBySlug() promote null returns into CategoryNotFoundException.
 *   2. All other methods delegate transparently to the query reader.
 *
 * All infrastructure is replaced by a PHPUnit mock — no database required.
 */
final class CategoryQueryServiceTest extends TestCase
{
    private MockObject&CategoryQueryReaderInterface $reader;
    private CategoryQueryService $service;

    protected function setUp(): void
    {
        $this->reader  = $this->createMock(CategoryQueryReaderInterface::class);
        $this->service = new CategoryQueryService($this->reader);
    }

    // ------------------------------------------------------------------ //
    //  Helpers
    // ------------------------------------------------------------------ //

    private function makeDto(int $id, ?int $parentId = null): CategoryDTO
    {
        return new CategoryDTO(
            id:           $id,
            parentId:     $parentId,
            name:         'Test',
            slug:         'test',
            isActive:     true,
            displayOrder: 1,
            createdAt:    '2026-01-01 00:00:00',
            updatedAt:    null,
            childCount:   0,
        );
    }

    /** @return array{data: list<never>, pagination: array{page: int, per_page: int, total: int, filtered: int}} */
    private function emptyPage(): array
    {
        return [
            'data'       => [],
            'pagination' => ['page' => 1, 'per_page' => 20, 'total' => 0, 'filtered' => 0],
        ];
    }

    // ================================================================== //
    //  getById()
    // ================================================================== //

    public function testGetByIdReturnsDtoWhenFound(): void
    {
        $dto = $this->makeDto(5);

        $this->reader->expects($this->once())
            ->method('findById')
            ->with(5)
            ->willReturn($dto);

        $result = $this->service->getById(5);

        $this->assertSame($dto, $result);
    }

    public function testGetByIdThrowsNotFoundWhenMissing(): void
    {
        $this->reader->expects($this->once())
            ->method('findById')
            ->with(99)
            ->willReturn(null);

        $this->expectException(CategoryNotFoundException::class);

        $this->service->getById(99);
    }

    // ================================================================== //
    //  getBySlug()
    // ================================================================== //

    public function testGetBySlugReturnsDtoWhenFound(): void
    {
        $dto = $this->makeDto(5);

        $this->reader->expects($this->once())
            ->method('findBySlug')
            ->with('electronics')
            ->willReturn($dto);

        $result = $this->service->getBySlug('electronics');

        $this->assertSame($dto, $result);
    }

    public function testGetBySlugThrowsNotFoundWhenMissing(): void
    {
        $this->reader->expects($this->once())
            ->method('findBySlug')
            ->with('ghost')
            ->willReturn(null);

        $this->expectException(CategoryNotFoundException::class);

        $this->service->getBySlug('ghost');
    }

    // ================================================================== //
    //  paginate()
    // ================================================================== //

    public function testPaginateDelegatesToReader(): void
    {
        $expected = $this->emptyPage();

        $this->reader->expects($this->once())
            ->method('listCategories')
            ->with(1, 20, null, [])
            ->willReturn($expected);

        $result = $this->service->paginate();

        $this->assertSame($expected, $result);
    }

    public function testPaginateForwardsAllParameters(): void
    {
        $expected = $this->emptyPage();

        $this->reader->expects($this->once())
            ->method('listCategories')
            ->with(3, 10, 'phone', ['is_active' => 1])
            ->willReturn($expected);

        $result = $this->service->paginate(
            page:          3,
            perPage:       10,
            globalSearch:  'phone',
            columnFilters: ['is_active' => 1],
        );

        $this->assertSame($expected, $result);
    }

    // ================================================================== //
    //  paginateSubCategories()
    // ================================================================== //

    public function testPaginateSubCategoriesDelegatesToReader(): void
    {
        $expected = $this->emptyPage();

        $this->reader->expects($this->once())
            ->method('listSubCategories')
            ->with(3, 1, 20, null, [])
            ->willReturn($expected);

        $result = $this->service->paginateSubCategories(parentId: 3);

        $this->assertSame($expected, $result);
    }

    // ================================================================== //
    //  activeRootList()
    // ================================================================== //

    public function testActiveRootListDelegatesToReader(): void
    {
        $list = [$this->makeDto(1), $this->makeDto(2)];

        $this->reader->expects($this->once())
            ->method('listActiveRootCategories')
            ->willReturn($list);

        $result = $this->service->activeRootList();

        $this->assertSame($list, $result);
    }

    // ================================================================== //
    //  activeSubList()
    // ================================================================== //

    public function testActiveSubListDelegatesToReader(): void
    {
        $list = [$this->makeDto(5, parentId: 1)];

        $this->reader->expects($this->once())
            ->method('listActiveSubCategories')
            ->with(1)
            ->willReturn($list);

        $result = $this->service->activeSubList(1);

        $this->assertSame($list, $result);
    }

    // ================================================================== //
    //  findSetting()
    // ================================================================== //

    public function testFindSettingReturnsDtoWhenFound(): void
    {
        $settingDto = new CategorySettingDTO(1, 5, 'color', 'blue', '2026-01-01', null);

        $this->reader->expects($this->once())
            ->method('findSetting')
            ->with(5, 'color')
            ->willReturn($settingDto);

        $result = $this->service->findSetting(5, 'color');

        $this->assertSame($settingDto, $result);
    }

    public function testFindSettingReturnsNullWhenNotFound(): void
    {
        $this->reader->expects($this->once())
            ->method('findSetting')
            ->with(5, 'missing')
            ->willReturn(null);

        $result = $this->service->findSetting(5, 'missing');

        $this->assertNull($result);
    }

    // ================================================================== //
    //  listSettingsPaginated()
    // ================================================================== //

    public function testListSettingsPaginatedDelegatesToReader(): void
    {
        $expected = $this->emptyPage();

        $this->reader->expects($this->once())
            ->method('listSettings')
            ->with(5, 1, 20, null, [])
            ->willReturn($expected);

        $result = $this->service->listSettingsPaginated(categoryId: 5);

        $this->assertSame($expected, $result);
    }
}




