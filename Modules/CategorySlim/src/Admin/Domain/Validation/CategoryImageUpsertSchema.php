<?php

declare(strict_types=1);

namespace Maatify\CategorySlim\Admin\Domain\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Rules\Primitive\EntityIdRule;
use Maatify\Validation\Rules\Primitive\StringRule;
use Maatify\Validation\Schemas\AbstractSchema;

final class CategoryImageUpsertSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'image_type' => [
                StringRule::required(min: 1, max: 50),
                ValidationErrorCodeEnum::REQUIRED_FIELD,
            ],
            'language_id' => [
                EntityIdRule::required(),
                ValidationErrorCodeEnum::REQUIRED_FIELD,
            ],
            'path' => [
                StringRule::required(min: 1, max: 500),
                ValidationErrorCodeEnum::REQUIRED_FIELD,
            ],
        ];
    }
}
