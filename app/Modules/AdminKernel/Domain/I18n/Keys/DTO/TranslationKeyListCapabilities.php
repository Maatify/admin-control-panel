<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Keys\DTO;

use Maatify\AdminKernel\Domain\List\ListCapabilities;

final class TranslationKeyListCapabilities
{
    public static function define(): ListCapabilities
    {
        return new ListCapabilities(
        // 🔍 Global search (free text)
            supportsGlobalSearch : true,
            searchableColumns    : [
                'domain',
                'key_part',
                'translation_key',
            ],

            // 🎯 Explicit column filters
            supportsColumnFilters: true,
            filterableColumns    : [
                'id'            => 'id',
                'domain'      => 'domain',
                'key_part'   => 'key_part',
                'translation_key'   => 'translation_key',
            ],

            supportsDateFilter   : false,
            dateColumn           : null
        );
    }
}

