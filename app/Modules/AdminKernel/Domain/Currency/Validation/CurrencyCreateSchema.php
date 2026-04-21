<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Currency\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Rules\Primitive\StringRule;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

final class CurrencyCreateSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'code' => [
                v::stringType()->notEmpty()->length(3, 3),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
            'name' => [
                StringRule::required(min: 1, max: 50),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
            'symbol' => [
                StringRule::required(min: 1, max: 10),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
            'is_active' => [
                v::optional(v::boolType()),
                ValidationErrorCodeEnum::INVALID_FORMAT
            ],
            'display_order' => [
                v::optional(v::intType()->min(0)),
                ValidationErrorCodeEnum::INVALID_FORMAT
            ],
        ];
    }
}
