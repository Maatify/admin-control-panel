<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\LanguageTranslationValue\List;

use Maatify\AdminKernel\Domain\List\ListCapabilities;

final class LanguageTranslationValueListCapabilities
{
    public static function define(): ListCapabilities
    {
        return new ListCapabilities(

        // 🔍 Global search (scope OR domain OR key_part OR value)
            supportsGlobalSearch : true,
            searchableColumns    : [
                'scope',
                'domain',
                'key_part',
                'value',
            ],

            // 🎯 Explicit column filters
            supportsColumnFilters: true,
            filterableColumns    : [
                'id'        => 'id',
                'scope'     => 'scope',
                'domain'    => 'domain',
                'key_part'  => 'key_part',
                'value'     => 'value',
            ],

            supportsDateFilter   : false,
            dateColumn           : null
        );
    }
}
