<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\ExchangeRates\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Rules\Primitive\IntRule;
use Maatify\Validation\Rules\Primitive\StringRule;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

final class RateUpdateSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'id' => [
                IntRule::required(min: 1),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
            'rate' => [
                v::stringType()->notEmpty(),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
            'recorded_at' => [
                v::optional(v::stringType()->notEmpty()),
                ValidationErrorCodeEnum::INVALID_FORMAT
            ],
        ];
    }
}
