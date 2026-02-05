<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Validation\Schemas\AppSettings;

use Maatify\Validation\Schemas\AbstractSchema;
use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Respect\Validation\Validator as v;

final class AppSettingsCreateSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'setting_group' => [
                v::stringType()->length(1, 64),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],

            'setting_key' => [
                v::stringType()->length(1, 64),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],

            'setting_value' => [
                v::stringType()->length(1, null),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],

            'is_active' => [
                v::optional(v::boolVal()),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
        ];
    }
}
