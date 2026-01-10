<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-10 02:03
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\Validation\Schemas;

use App\Modules\Validation\Enum\ValidationErrorCodeEnum;
use App\Modules\Validation\Rules\PaginationRule;
use App\Modules\Validation\Rules\SearchQueryRule;
use App\Modules\Validation\Rules\DateRangeRule;

final class SharedListQuerySchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [

            // Pagination
            'page' => [
                PaginationRule::page(),
                ValidationErrorCodeEnum::INVALID_VALUE,
            ],

            'per_page' => [
                PaginationRule::perPage(100),
                ValidationErrorCodeEnum::INVALID_VALUE,
            ],

            // Search
            'search'   => [
                SearchQueryRule::rule(),
                ValidationErrorCodeEnum::INVALID_VALUE,
            ],

            // Date range
            'date'     => [
                DateRangeRule::rule(),
                ValidationErrorCodeEnum::INVALID_VALUE,
            ],
        ];
    }
}
