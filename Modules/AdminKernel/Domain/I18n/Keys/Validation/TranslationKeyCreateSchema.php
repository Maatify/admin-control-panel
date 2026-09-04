<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Keys\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Rules\Primitive\StringRule;
use Maatify\Validation\Rules\Semantic\I18nCodeRule;
use Maatify\Validation\Schemas\AbstractSchema;

final class TranslationKeyCreateSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [

            'domain_code' => [
                I18nCodeRule::rule(1, 64),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],

            'key_name' => [
                I18nCodeRule::rule(1, 128),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],

            'description' => [
                StringRule::optional(min: 0, max: 255),
                ValidationErrorCodeEnum::INVALID_FORMAT
            ],
        ];
    }
}
