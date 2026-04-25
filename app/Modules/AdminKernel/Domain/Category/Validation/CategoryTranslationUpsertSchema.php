<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Category\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

final class CategoryTranslationUpsertSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'language_id' => [
                v::intType()->min(1),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
            'translated_name' => [
                v::stringType()->notEmpty()->length(1, 100),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
            'translated_description' => [
                v::optional(v::stringType()->length(0, 1000)),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
        ];
    }
}

