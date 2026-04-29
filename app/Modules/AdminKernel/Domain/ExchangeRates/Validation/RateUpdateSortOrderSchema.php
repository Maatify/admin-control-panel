<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\ExchangeRates\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Rules\Primitive\IntRule;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

final class RateUpdateSortOrderSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'id' => [
                IntRule::required(min: 1),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
            'display_order' => [
                IntRule::required(min: 0),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
        ];
    }
}
