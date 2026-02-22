<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\ContentDocuments\List;

use Maatify\AdminKernel\Domain\List\ListCapabilities;

final class ContentDocumentVersionsListCapabilities
{
    public static function define(): ListCapabilities
    {
        return new ListCapabilities(
        // 🔍 Global search (free text)
            supportsGlobalSearch: true,
            searchableColumns: [
                'document_id',
                'version',
            ],

            // 🎯 Explicit column filters
            supportsColumnFilters: true,
            filterableColumns: [
                'document_id'         => 'document_id',
                'version'             => 'version',
                'is_active'           => 'is_active',
                'requires_acceptance' => 'requires_acceptance',
                'status' => 'status',
            ],

            // 📅 Date filtering
            supportsDateFilter: true,
            dateColumn: 'created_at'
        );
    }
}