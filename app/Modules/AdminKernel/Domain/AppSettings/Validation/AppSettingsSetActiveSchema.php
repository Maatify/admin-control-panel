<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\AppSettings\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Rules\Primitive\BooleanRule;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;
use Maatify\Validation\Rules\Primitive\StringRule;
use Maatify\Validation\Rules\StringPatternRule;

final class AppSettingsSetActiveSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'setting_group' => [
                StringRule::required(1, 64, StringPatternRule::MACHINE_KEY_PATTERN),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],

            'setting_key' => [
                StringRule::required(1, 64, StringPatternRule::MACHINE_KEY_PATTERN),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],

            'is_active' => [
                BooleanRule::required(),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
        ];
    }
}
