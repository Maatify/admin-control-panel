<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\ContentDocuments\List;

use Maatify\AdminKernel\Domain\List\ListCapabilities;

final class ContentDocumentTranslationsListCapabilities
{
    public static function define(): ListCapabilities
    {
        return new ListCapabilities(
        // 🔍 Global search (free text)
            supportsGlobalSearch: true,
            searchableColumns: [
                'language_code',
                'language_name',
            ],

            // 🎯 Explicit column filters
            supportsColumnFilters: true,
            filterableColumns: [
                'language_id'   => 'language_id',
                'has_translation' => 'has_translation',
            ],

            supportsDateFilter   : false,
            dateColumn           : null
        );
    }
}