<?php

declare(strict_types=1);

namespace Maatify\CategorySlim\Admin\Domain\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Rules\Primitive\StringRule;
use Maatify\Validation\Schemas\AbstractSchema;

final class CategorySettingDeleteSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'key' => [
                StringRule::required(min: 1, max: 100),
                ValidationErrorCodeEnum::REQUIRED_FIELD,
            ],
        ];
    }
}
