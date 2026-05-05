<?php

declare(strict_types=1);

namespace Maatify\GeoSlim\Admin\Domain\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Rules\Primitive\StringRule;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

final class CountryCreateSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'code' => [
                v::stringType()->notEmpty()->length(2, 2),
                ValidationErrorCodeEnum::REQUIRED_FIELD,
            ],
            'name' => [
                StringRule::required(min: 1, max: 100),
                ValidationErrorCodeEnum::REQUIRED_FIELD,
            ],
            'icon' => [
                StringRule::optional(min: 1, max: 255),
                ValidationErrorCodeEnum::INVALID_FORMAT,
            ],
            'is_active' => [
                v::optional(v::boolType()),
                ValidationErrorCodeEnum::INVALID_FORMAT,
            ],
        ];
    }
}

