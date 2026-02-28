<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Translations\List;

use Maatify\AdminKernel\Domain\List\ListCapabilities;

final class I18nScopeDomainTranslationsListCapabilities
{
    public static function define(): ListCapabilities
    {
        return new ListCapabilities(
            supportsGlobalSearch: true,

            searchableColumns: [
                'key_part',
                'description',
                'value',
                'language_code',
                'language_name',
            ],

            supportsColumnFilters: true,

            filterableColumns: [
                'key_id'        => 'key_id',
                'key_part'      => 'key_part',
                'language_id'   => 'language_id',
                'value'=> 'value',
            ],

            supportsDateFilter: false,
            dateColumn: null
        );
    }
}
