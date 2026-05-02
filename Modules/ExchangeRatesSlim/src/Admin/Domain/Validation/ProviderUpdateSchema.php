<?php

declare(strict_types=1);

namespace Maatify\ExchangeRatesSlim\Admin\Domain\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Rules\Primitive\StringRule;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

final class ProviderUpdateSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'id' => [
                v::intType()->min(1),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
            'name' => [
                StringRule::required(min: 1, max: 100),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
            'description' => [
                v::optional(v::stringType()),
                ValidationErrorCodeEnum::INVALID_FORMAT
            ],
        ];
    }
}
