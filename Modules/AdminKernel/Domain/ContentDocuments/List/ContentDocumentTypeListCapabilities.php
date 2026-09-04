<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\ContentDocuments\List;

use Maatify\AdminKernel\Domain\List\ListCapabilities;

class ContentDocumentTypeListCapabilities
{
    public static function define(): ListCapabilities
    {
        return new ListCapabilities(
        // 🔍 Global search (free text)
            supportsGlobalSearch : true,
            searchableColumns    : [
                'key',
            ],

            // 🎯 Explicit column filters
            supportsColumnFilters: true,
            filterableColumns    : [
                'id' => 'id',
                'key' => 'key',
                'requires_acceptance_default' => 'requires_acceptance_default',
                'is_system' => 'is_system',
            ],

            supportsDateFilter   : false,
            dateColumn           : null
        );
    }
}
