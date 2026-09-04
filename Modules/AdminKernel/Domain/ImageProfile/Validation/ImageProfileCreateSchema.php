<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\ImageProfile\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

final class ImageProfileCreateSchema extends AbstractSchema
{
    protected function rules(): array
    {
        $nullableString = static fn (int $max): \Respect\Validation\Validatable => v::optional(v::anyOf(v::nullType(), v::stringType()->length(1, $max)));
        $nullableInt = static fn (int $min = 0, ?int $max = null): \Respect\Validation\Validatable => v::optional(v::anyOf(v::nullType(), v::intType()->min($min)->max($max ?? PHP_INT_MAX)));

        return [
            'code' => [
                v::stringType()->notEmpty()->length(1, 64),
                ValidationErrorCodeEnum::REQUIRED_FIELD,
            ],
            'display_name' => [$nullableString(128), ValidationErrorCodeEnum::INVALID_FORMAT],
            'min_width' => [$nullableInt(), ValidationErrorCodeEnum::INVALID_FORMAT],
            'min_height' => [$nullableInt(), ValidationErrorCodeEnum::INVALID_FORMAT],
            'max_width' => [$nullableInt(), ValidationErrorCodeEnum::INVALID_FORMAT],
            'max_height' => [$nullableInt(), ValidationErrorCodeEnum::INVALID_FORMAT],
            'max_size_bytes' => [$nullableInt(), ValidationErrorCodeEnum::INVALID_FORMAT],
            'allowed_extensions' => [$nullableString(255), ValidationErrorCodeEnum::INVALID_FORMAT],
            'allowed_mime_types' => [v::optional(v::anyOf(v::nullType(), v::stringType())), ValidationErrorCodeEnum::INVALID_FORMAT],
            'is_active' => [v::optional(v::boolType()), ValidationErrorCodeEnum::INVALID_FORMAT],
            'notes' => [v::optional(v::anyOf(v::nullType(), v::stringType())), ValidationErrorCodeEnum::INVALID_FORMAT],
            'min_aspect_ratio' => [$nullableString(16), ValidationErrorCodeEnum::INVALID_FORMAT],
            'max_aspect_ratio' => [$nullableString(16), ValidationErrorCodeEnum::INVALID_FORMAT],
            'requires_transparency' => [v::optional(v::boolType()), ValidationErrorCodeEnum::INVALID_FORMAT],
            'preferred_format' => [$nullableString(10), ValidationErrorCodeEnum::INVALID_FORMAT],
            'preferred_quality' => [$nullableInt(1, 100), ValidationErrorCodeEnum::INVALID_FORMAT],
            'variants' => [v::optional(v::anyOf(v::nullType(), v::stringType())), ValidationErrorCodeEnum::INVALID_FORMAT],
        ];
    }
}
