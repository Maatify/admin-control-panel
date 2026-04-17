<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\ImageProfile\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

final class ImageProfileUpdateSchema extends AbstractSchema
{
    protected function rules(): array
    {
        $nullableString = static fn (int $max): \Respect\Validation\Validatable => v::anyOf(v::nullType(), v::stringType()->length(1, $max));
        $nullableInt = static fn (int $min = 0, ?int $max = null): \Respect\Validation\Validatable => v::anyOf(v::nullType(), v::intType()->min($min)->max($max ?? PHP_INT_MAX));

        return [
            'id' => [v::intType()->min(1), ValidationErrorCodeEnum::REQUIRED_FIELD],
            'code' => [v::stringType()->notEmpty()->length(1, 64), ValidationErrorCodeEnum::REQUIRED_FIELD],
            'display_name' => [$nullableString(128), ValidationErrorCodeEnum::REQUIRED_FIELD],
            'min_width' => [$nullableInt(), ValidationErrorCodeEnum::REQUIRED_FIELD],
            'min_height' => [$nullableInt(), ValidationErrorCodeEnum::REQUIRED_FIELD],
            'max_width' => [$nullableInt(), ValidationErrorCodeEnum::REQUIRED_FIELD],
            'max_height' => [$nullableInt(), ValidationErrorCodeEnum::REQUIRED_FIELD],
            'max_size_bytes' => [$nullableInt(), ValidationErrorCodeEnum::REQUIRED_FIELD],
            'allowed_extensions' => [$nullableString(255), ValidationErrorCodeEnum::REQUIRED_FIELD],
            'allowed_mime_types' => [v::anyOf(v::nullType(), v::stringType()), ValidationErrorCodeEnum::REQUIRED_FIELD],
            'is_active' => [v::boolType(), ValidationErrorCodeEnum::REQUIRED_FIELD],
            'notes' => [v::anyOf(v::nullType(), v::stringType()), ValidationErrorCodeEnum::REQUIRED_FIELD],
            'min_aspect_ratio' => [$nullableString(16), ValidationErrorCodeEnum::REQUIRED_FIELD],
            'max_aspect_ratio' => [$nullableString(16), ValidationErrorCodeEnum::REQUIRED_FIELD],
            'requires_transparency' => [v::boolType(), ValidationErrorCodeEnum::REQUIRED_FIELD],
            'preferred_format' => [$nullableString(10), ValidationErrorCodeEnum::REQUIRED_FIELD],
            'preferred_quality' => [$nullableInt(1, 100), ValidationErrorCodeEnum::REQUIRED_FIELD],
            'variants' => [v::anyOf(v::nullType(), v::stringType()), ValidationErrorCodeEnum::REQUIRED_FIELD],
        ];
    }
}
