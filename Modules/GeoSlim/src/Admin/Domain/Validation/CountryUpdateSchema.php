<?php

declare(strict_types=1);

namespace Maatify\GeoSlim\Admin\Domain\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Rules\Primitive\BooleanRule;
use Maatify\Validation\Rules\Primitive\EntityIdRule;
use Maatify\Validation\Rules\Primitive\StringRule;
use Maatify\Validation\Schemas\AbstractSchema;

final class CountryUpdateSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'id' => [
                EntityIdRule::required(),
                ValidationErrorCodeEnum::REQUIRED_FIELD,
            ],
            'code' => [
                StringRule::required(min: 2, max: 2),
                ValidationErrorCodeEnum::REQUIRED_FIELD,
            ],
            'name' => [
                StringRule::required(min: 1, max: 100),
                ValidationErrorCodeEnum::REQUIRED_FIELD,
            ],
            'phone_code' => [
                StringRule::optional(min: 1, max: 10),
                ValidationErrorCodeEnum::INVALID_FORMAT,
            ],
            'currency' => [
                StringRule::optional(min: 1, max: 10),
                ValidationErrorCodeEnum::INVALID_FORMAT,
            ],
            'icon' => [
                StringRule::optional(min: 1, max: 255),
                ValidationErrorCodeEnum::INVALID_FORMAT,
            ],
            'is_active' => [
                BooleanRule::required(),
                ValidationErrorCodeEnum::REQUIRED_FIELD,
            ],
        ];
    }
}
