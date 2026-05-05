<?php

declare(strict_types=1);

namespace Maatify\GeoSlim\Admin\Domain\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

final class CitySetActiveSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'id' => [
                v::intType()->min(1),
                ValidationErrorCodeEnum::REQUIRED_FIELD,
            ],
            'is_active' => [
                v::boolType(),
                ValidationErrorCodeEnum::REQUIRED_FIELD,
            ],
        ];
    }
}

