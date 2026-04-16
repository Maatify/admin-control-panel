<?php

declare(strict_types=1);

namespace Maatify\Currency\Integration\AdminKernel\Support\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
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
                v::stringType()->notEmpty()->length(1, 50),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
            'symbol' => [
                v::stringType()->notEmpty()->length(1, 10),
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
