<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Language\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Rules\PositiveEntityIdRule;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

final class LanguageUpdateNameSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'language_id' => [
                PositiveEntityIdRule::rule(),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],

            'name' => [
                v::stringType()->length(1, 255),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
        ];
    }
}
