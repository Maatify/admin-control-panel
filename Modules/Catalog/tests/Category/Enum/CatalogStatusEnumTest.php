<?php

declare(strict_types=1);

namespace Maatify\Catalog\Tests\Category\Enum;

use Maatify\Catalog\Category\Enum\CatalogStatusEnum;
use PHPUnit\Framework\TestCase;

final class CatalogStatusEnumTest extends TestCase
{
    public function testItExposesTheSchemaStatusValues(): void
    {
        self::assertSame(['active', 'inactive'], array_map(
            static fn (CatalogStatusEnum $status): string => $status->value,
            CatalogStatusEnum::cases(),
        ));
    }
}
