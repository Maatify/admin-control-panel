<?php

declare(strict_types=1);

namespace Maatify\CategorySlim\Admin\Domain\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Rules\Primitive\BooleanRule;
use Maatify\Validation\Rules\Primitive\EntityIdRule;
use Maatify\Validation\Rules\Primitive\StringRule;
use Maatify\Validation\Rules\Semantic\SlugRule;
use Maatify\Validation\Schemas\AbstractSchema;

final class CategoryUpdateSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'id' => [
                EntityIdRule::required(),
                ValidationErrorCodeEnum::REQUIRED_FIELD,
            ],
            'name' => [
                StringRule::required(min: 1, max: 100),
                ValidationErrorCodeEnum::REQUIRED_FIELD,
            ],
            'slug' => [
                SlugRule::required(max: 100),
                ValidationErrorCodeEnum::REQUIRED_FIELD,
            ],
            'parent_id' => [
                EntityIdRule::optional(),
                ValidationErrorCodeEnum::INVALID_FORMAT,
            ],
            'is_active' => [
                BooleanRule::required(),
                ValidationErrorCodeEnum::REQUIRED_FIELD,
            ],
            'display_order' => [
                EntityIdRule::required(),
                ValidationErrorCodeEnum::REQUIRED_FIELD,
            ],
            'notes' => [
                StringRule::optional(min: 0, max: 5000),
                ValidationErrorCodeEnum::INVALID_FORMAT,
            ],
            'description' => [
                StringRule::optional(min: 0, max: 2000),
                ValidationErrorCodeEnum::INVALID_FORMAT,
            ],
        ];
    }
}

