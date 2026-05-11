<?php

declare(strict_types=1);

namespace Maatify\SettingsSlim\Admin\Domain\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

final class SettingUpdateSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'setting_key' => [
                v::stringType()->notEmpty()->length(1, 255),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
            'value' => [
                v::stringType()->length(0, 255),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
        ];
    }
}
