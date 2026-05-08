<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Language\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Rules\Primitive\EntityIdRule;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;
use Maatify\Validation\Rules\Primitive\StringRule;

final class LanguageUpdateCodeSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'language_id' => [
                EntityIdRule::required(),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],

            'code' => [
                StringRule::required(1, 32),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
        ];
    }
}
