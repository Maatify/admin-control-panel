<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Category\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

final class CategoryCreateSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
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
                v::optional(v::boolType()),
                ValidationErrorCodeEnum::INVALID_FORMAT,
            ],
            'display_order' => [
                v::optional(v::intType()->min(0)),
                ValidationErrorCodeEnum::INVALID_FORMAT,
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

