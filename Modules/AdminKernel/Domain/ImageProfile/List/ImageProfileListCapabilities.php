<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\ImageProfile\List;

use Maatify\AdminKernel\Domain\List\ListCapabilities;

final class ImageProfileListCapabilities
{
    public static function define(): ListCapabilities
    {
        return new ListCapabilities(
            supportsGlobalSearch: true,
            searchableColumns: ['code', 'display_name'],
            supportsColumnFilters: true,
            filterableColumns: [
                'id' => 'id',
                'code' => 'code',
                'is_active' => 'is_active',
            ],
            supportsDateFilter: false,
            dateColumn: null
        );
    }
}
