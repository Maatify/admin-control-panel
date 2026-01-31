<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-09 01:31
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\Validation\Schemas;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Rules\EmailRule;

final class AdminCreateSchema extends AbstractSchema
{
    /**
     * @return array<string, array{
     *     0: \Respect\Validation\Validatable,
     *     1: \Maatify\Validation\Enum\ValidationErrorCodeEnum
     * }>
     */
    protected function rules(): array
    {
        return [
            'email' => [
                EmailRule::rule(),
                ValidationErrorCodeEnum::INVALID_EMAIL,
            ],

            'display_name' => [
                \Respect\Validation\Validator::stringType()
                    ->notEmpty()
                    ->length(2, 100),
                ValidationErrorCodeEnum::INVALID_DISPLAY_NAME,
            ],
        ];
    }
}


