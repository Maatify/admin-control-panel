<?php

declare(strict_types=1);

namespace Maatify\CurrencySlim\Admin\Domain\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;
use Maatify\Validation\Rules\Primitive\StrictEntityIdRule;

final class CurrencySetActiveSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'id' => [
                StrictEntityIdRule::required(),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
            'is_active' => [
                v::boolType(),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ]
        ];
    }
}
