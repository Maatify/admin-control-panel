<?php

declare(strict_types=1);

namespace Maatify\ExchangeRatesSlim\Admin\Domain\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Rules\Primitive\StringRule;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

final class RateCreateSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'provider_id' => [
                v::intType()->min(1),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
            'base_currency_code' => [
                StringRule::required(3, 3),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
            'target_currency_code' => [
                StringRule::required(3, 3),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
            'rate' => [
                v::stringType()->notEmpty(), // format ^\d+(?:\.\d{1,10})?$ is verified in Command
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
            'recorded_at' => [
                v::optional(v::stringType()->notEmpty()),
                ValidationErrorCodeEnum::INVALID_FORMAT
            ],
        ];
    }
}
