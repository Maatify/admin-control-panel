<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\ExchangeRates\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;

use Maatify\Validation\Rules\Primitive\StringRule;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

final class RateHistoryQuerySchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'rate_id' => [
                v::intType()->min(1),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
            'page' => [
                v::optional(v::intType()->min(1)),
                ValidationErrorCodeEnum::INVALID_FORMAT
            ],
            'per_page' => [
                v::optional(v::intType()->min(1)),
                ValidationErrorCodeEnum::INVALID_FORMAT
            ],
        ];
    }
}
