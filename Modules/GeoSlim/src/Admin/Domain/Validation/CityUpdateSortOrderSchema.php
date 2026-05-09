<?php

declare(strict_types=1);

namespace Maatify\GeoSlim\Admin\Domain\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Rules\Primitive\EntityIdRule;
use Maatify\Validation\Schemas\AbstractSchema;

final class CityUpdateSortOrderSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'id' => [
                EntityIdRule::required(),
                ValidationErrorCodeEnum::REQUIRED_FIELD,
            ],
            'display_order' => [
                EntityIdRule::required(),
                ValidationErrorCodeEnum::REQUIRED_FIELD,
            ],
        ];
    }
}
