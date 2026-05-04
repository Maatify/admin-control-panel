<?php

declare(strict_types=1);

namespace Maatify\Category\Admin\Domain\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

final class CategoryTranslationDeleteSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'language_id' => [
                v::intType()->min(1),
                ValidationErrorCodeEnum::REQUIRED_FIELD,
            ],
        ];
    }
}

