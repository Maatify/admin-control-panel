<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Language\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Rules\Primitive\BooleanRule;
use Maatify\Validation\Rules\Primitive\EntityIdRule;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

final class LanguageSetActiveSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'language_id' => [
                EntityIdRule::required(),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],

            'is_active' => [
                BooleanRule::required(),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
        ];
    }
}
