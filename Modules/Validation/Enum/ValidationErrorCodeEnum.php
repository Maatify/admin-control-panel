<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-09 01:38
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Validation\Enum;

enum ValidationErrorCodeEnum: string
{
    /*
     |--------------------------------------------------------------------------
     | Generic / Structural Errors (APIs, LIST, Filters)
     |--------------------------------------------------------------------------
     */
    case REQUIRED_FIELD = 'required_field';
    case INVALID_VALUE  = 'invalid_value';
    case INVALID_FORMAT = 'invalid_format';
    case OUT_OF_RANGE   = 'out_of_range';

    /*
     |--------------------------------------------------------------------------
     | Semantic Field Errors (Auth / Identity)
     |--------------------------------------------------------------------------
     */
    case INVALID_EMAIL    = 'invalid_email';
    case INVALID_PASSWORD = 'invalid_password';
    case INVALID_NAME     = 'invalid_name';
    case INVALID_DISPLAY_NAME = 'invalid_display_name';
}