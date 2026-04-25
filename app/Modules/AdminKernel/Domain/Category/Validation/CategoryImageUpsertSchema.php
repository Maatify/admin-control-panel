<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Category\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

final class CategoryImageUpsertSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'image_type' => [
                v::stringType()->in(['image', 'mobile_image', 'api_image', 'website_image']),
                ValidationErrorCodeEnum::REQUIRED_FIELD,
            ],
            'language_id' => [
                v::intType()->min(1),
                ValidationErrorCodeEnum::REQUIRED_FIELD,
            ],
            'path' => [
                v::stringType()->notEmpty()->length(1, 500),
                ValidationErrorCodeEnum::REQUIRED_FIELD,
            ],
        ];
    }
}

