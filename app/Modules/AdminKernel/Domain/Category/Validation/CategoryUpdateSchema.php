<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Category\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

final class CategoryUpdateSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'id' => [
                v::intType()->min(1),
                ValidationErrorCodeEnum::REQUIRED_FIELD,
            ],
            'name' => [
                v::stringType()->notEmpty()->length(1, 100),
                ValidationErrorCodeEnum::REQUIRED_FIELD,
            ],
            'slug' => [
                v::stringType()->notEmpty()->length(1, 100),
                ValidationErrorCodeEnum::REQUIRED_FIELD,
            ],
            'parent_id' => [
                v::optional(v::intType()->min(1)),
                ValidationErrorCodeEnum::INVALID_FORMAT,
            ],
            'is_active' => [
                v::boolType(),
                ValidationErrorCodeEnum::REQUIRED_FIELD,
            ],
            'display_order' => [
                v::intType()->min(1),
                ValidationErrorCodeEnum::REQUIRED_FIELD,
            ],
            'notes' => [
                v::optional(v::stringType()->length(0, 5000)),
                ValidationErrorCodeEnum::INVALID_FORMAT,
            ],
            'description' => [
                v::optional(v::stringType()->length(0, 2000)),
                ValidationErrorCodeEnum::INVALID_FORMAT,
            ],
        ];
    }
}

