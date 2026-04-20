<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Language\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Rules\Primitive\EntityIdRule;
use Maatify\Validation\Rules\Primitive\StringRule;
use Maatify\Validation\Schemas\AbstractSchema;

final class LanguageUpdateNameSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'language_id' => [
                EntityIdRule::required(),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],

            'name' => [
                StringRule::required(min: 1, max: 255),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
        ];
    }
}
