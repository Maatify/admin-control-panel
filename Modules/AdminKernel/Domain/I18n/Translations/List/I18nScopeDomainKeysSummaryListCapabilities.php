<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-13 00:46
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Translations\List;

use Maatify\AdminKernel\Domain\List\ListCapabilities;

final class I18nScopeDomainKeysSummaryListCapabilities
{
    public static function define(): ListCapabilities
    {
        return new ListCapabilities(
            supportsGlobalSearch: true,

            searchableColumns: [
                'key_part',
                'description',
            ],

            supportsColumnFilters: true,

            filterableColumns: [
                'key_id'        => 'k.id',
                'key_part'      => 'k.key_part',
                'missing'       => 'missing',
                'language_id'   => 'language_id',

                // NEW: language activity filter
                'language_is_active'=> 'language_is_active',
            ],

            supportsDateFilter: false,
            dateColumn: null
        );
    }
}
