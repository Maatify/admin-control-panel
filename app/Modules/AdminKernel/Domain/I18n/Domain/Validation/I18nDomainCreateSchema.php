<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Domain\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Rules\Primitive\BooleanRule;
use Maatify\Validation\Rules\Primitive\StringRule;
use Maatify\Validation\Rules\Semantic\I18nCodeRule;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

final class I18nDomainCreateSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'code' => [
                I18nCodeRule::rule(1, 50),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],

            'name' => [
                StringRule::required(min: 1, max: 100),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],

            'description' => [
                StringRule::optional(min: 0, max: 255),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],

            'is_active' => [
                BooleanRule::optional(),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
        ];
    }
}
