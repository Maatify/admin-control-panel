<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\TranslationValue\List;

use Maatify\AdminKernel\Domain\List\ListCapabilities;

final class TranslationValueListCapabilities
{
    public static function define(): ListCapabilities
    {
        return new ListCapabilities(
            // 🔍 Global search (key_name OR value)
            supportsGlobalSearch : true,
            searchableColumns    : [
                'key_name',
                'value',
            ],

            // 🎯 Explicit column filters
            supportsColumnFilters: true,
            filterableColumns    : [
                'id'        => 'id',
                'key_name'  => 'key_name',
                'value'     => 'value',
            ],

            supportsDateFilter   : false,
            dateColumn           : null
        );
    }
}

