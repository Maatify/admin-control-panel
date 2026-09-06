<?php

declare(strict_types=1);

namespace Maatify\Catalog\Category\Enum;

enum CatalogStatusEnum: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}
