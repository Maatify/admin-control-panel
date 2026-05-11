<?php

declare(strict_types=1);

namespace Maatify\SettingsSlim\Admin\Domain\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Rules\Primitive\StringRule;
use Maatify\Validation\Schemas\AbstractSchema;

final class SettingGetSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'setting_key' => [
                StringRule::required(min: 1, max: 255),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
        ];
    }
}
