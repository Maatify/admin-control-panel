<?php

declare(strict_types=1);

namespace Maatify\ExchangeRatesSlim\Admin\Domain\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;
use Maatify\Validation\Rules\Primitive\StrictEntityIdRule;
use Maatify\Validation\Rules\Primitive\StrictBooleanRule;

final class RateSetActiveSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'id' => [
                StrictEntityIdRule::required(),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
            'is_active' => [
                StrictBooleanRule::required(),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
        ];
    }
}
