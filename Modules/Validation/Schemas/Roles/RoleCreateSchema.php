<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-27 00:01
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Validation\Schemas\Roles;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

class RoleCreateSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            // ─────────────────────────────
            // Technical role key
            // ─────────────────────────────
            'name'         => [
                v::stringType()
                    ->notEmpty()
                    ->length(3, 190)
                    ->regex('/^[a-z][a-z0-9_.-]*$/'),
                ValidationErrorCodeEnum::INVALID_VALUE
            ],

            // ─────────────────────────────
            // Optional UI metadata fields
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
