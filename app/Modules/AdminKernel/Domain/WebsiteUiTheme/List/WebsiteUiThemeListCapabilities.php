<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\WebsiteUiTheme\List;

use Maatify\AdminKernel\Domain\List\ListCapabilities;

final class WebsiteUiThemeListCapabilities
{
    public static function define(): ListCapabilities
    {
        return new ListCapabilities(
            supportsGlobalSearch: true,
            searchableColumns: ['entity_type', 'theme_file', 'display_name'],
            supportsColumnFilters: true,
            filterableColumns: [
                'id' => 'id',
                'entity_type' => 'entity_type',
                'theme_file' => 'theme_file',
            ],
            supportsDateFilter: false,
            dateColumn: null
        );
    }
}
