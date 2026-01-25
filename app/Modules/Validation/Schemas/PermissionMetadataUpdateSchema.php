<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-26 00:49
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\Validation\Schemas;

use App\Modules\Validation\Enum\ValidationErrorCodeEnum;
use Respect\Validation\Validator as v;

class PermissionMetadataUpdateSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            // ─────────────────────────────
            // Permission ID
            // ─────────────────────────────
//            'id' => [
//                v::intType()->positive(),
//                ValidationErrorCodeEnum::REQUIRED_FIELD
//            ],

            // ─────────────────────────────
            // Optional metadata fields
            // ─────────────────────────────
            'display_name' => [
                v::optional(
                    v::stringType()->length(1, 128)
                ),
                ValidationErrorCodeEnum::INVALID_DISPLAY_NAME
            ],

            'description' => [
                v::optional(
                    v::stringType()->length(1, 255)
                ),
                ValidationErrorCodeEnum::INVALID_VALUE
            ],
        ];
    }
}
