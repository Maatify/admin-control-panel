<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\AppSettings\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

final class AppSettingsSetActiveSchema extends AbstractSchema
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

            'is_active' => [
                v::boolVal(),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
        ];
    }
}
