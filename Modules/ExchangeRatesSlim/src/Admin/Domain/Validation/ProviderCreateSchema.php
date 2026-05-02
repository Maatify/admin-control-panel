<?php

declare(strict_types=1);

namespace Maatify\ExchangeRatesSlim\Admin\Domain\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Rules\Primitive\StringRule;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

final class ProviderCreateSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'name' => [
                StringRule::required(min: 1, max: 100),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
            'code' => [
                StringRule::required(min: 1, max: 50),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
            'description' => [
                v::optional(v::stringType()),
                ValidationErrorCodeEnum::INVALID_FORMAT
            ],
        ];
    }
}
