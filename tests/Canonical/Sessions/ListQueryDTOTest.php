<?php

declare(strict_types=1);

namespace Tests\Canonical\Sessions;

use App\Domain\List\ListQueryDTO;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class ListQueryDTOTest extends TestCase
{
    public function test_normalization_defaults(): void
    {
        $input = [];
        $dto = ListQueryDTO::fromArray($input);

        $this->assertSame(1, $dto->page);
        $this->assertSame(20, $dto->perPage);
        $this->assertNull($dto->globalSearch);
        $this->assertEmpty($dto->columnFilters);
        $this->assertNull($dto->dateFrom);
        $this->assertNull($dto->dateTo);
    }

    public function test_page_per_page_clamping(): void
    {
        $input = [
            'page' => 0,
            'per_page' => -5,
        ];
        $dto = ListQueryDTO::fromArray($input);

        $this->assertSame(1, $dto->page, 'Page should be min 1');
        $this->assertSame(1, $dto->perPage, 'PerPage should be min 1');
        // Note: DTO implementation uses max(1, input).
        // Schema might enforce max(100) but DTO might not.
    }

    public function test_removes_empty_optional_blocks(): void
    {
        $input = [
            'search' => [],
            'date' => [],
        ];
        $dto = ListQueryDTO::fromArray($input);

        $this->assertNull($dto->globalSearch);
        $this->assertEmpty($dto->columnFilters);
        $this->assertNull($dto->dateFrom);
        $this->assertNull($dto->dateTo);
    }

    public function test_trims_global_search(): void
    {
        $input = [
            'search' => ['global' => '  hello  '],
        ];
        $dto = ListQueryDTO::fromArray($input);

        $this->assertSame('hello', $dto->globalSearch);
    }

    public function test_parses_date_strings(): void
    {
        $input = [
            'date' => [
                'from' => '2023-01-01',
                'to' => '2023-01-31',
            ],
        ];
        $dto = ListQueryDTO::fromArray($input);

        $this->assertInstanceOf(DateTimeImmutable::class, $dto->dateFrom);
        $this->assertInstanceOf(DateTimeImmutable::class, $dto->dateTo);
        $this->assertSame('2023-01-01', $dto->dateFrom->format('Y-m-d'));
        $this->assertSame('2023-01-31', $dto->dateTo->format('Y-m-d'));
    }

    public function test_ignores_empty_search_columns(): void
    {
        $input = [
            'search' => [
                'columns' => [
                    'status' => 'active',
                    'empty_col' => '',
                    'null_col' => null,
                ],
            ],
        ];
        $dto = ListQueryDTO::fromArray($input);

        $this->assertArrayHasKey('status', $dto->columnFilters);
        $this->assertArrayNotHasKey('empty_col', $dto->columnFilters);
        $this->assertArrayNotHasKey('null_col', $dto->columnFilters);
    }
}
