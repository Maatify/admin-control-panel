<?php

declare(strict_types=1);

namespace Maatify\CategorySlim\Admin\Domain\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Rules\Primitive\EntityIdRule;
use Maatify\Validation\Rules\Primitive\StringRule;
use Maatify\Validation\Schemas\AbstractSchema;

final class CategorySettingUpsertSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'key' => [
                StringRule::required(min: 1, max: 100),
                ValidationErrorCodeEnum::REQUIRED_FIELD,
            ],
            'value' => [
                StringRule::required(min: 1, max: 5000),
                ValidationErrorCodeEnum::REQUIRED_FIELD,
            ],
        ];
    }
}
