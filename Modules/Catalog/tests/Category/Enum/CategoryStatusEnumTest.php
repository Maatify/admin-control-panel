<?php

declare(strict_types=1);

namespace Maatify\Catalog\Tests\Category\Enum;

use Maatify\Catalog\Category\Enum\CategoryStatusEnum;
use PHPUnit\Framework\TestCase;

final class CategoryStatusEnumTest extends TestCase
{
    public function testItExposesTheSchemaStatusValues(): void
    {
        self::assertSame(['active', 'inactive'], array_map(
            static fn (CategoryStatusEnum $status): string => $status->value,
            CategoryStatusEnum::cases(),
        ));
    }
}
