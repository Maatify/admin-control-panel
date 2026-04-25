<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Category\List;

use Maatify\AdminKernel\Domain\List\ListCapabilities;

final class CategoryListCapabilities
{
    public static function define(): ListCapabilities
    {
        return new ListCapabilities(
            supportsGlobalSearch:  true,
            searchableColumns:     ['name', 'slug'],
            supportsColumnFilters: true,
            filterableColumns:     [
                'id'        => 'id',
                'name'      => 'name',
                'slug'      => 'slug',
                'is_active' => 'is_active',
                'parent_id' => 'parent_id',
            ],
            supportsDateFilter: false,
            dateColumn:         null,
        );
    }
}

