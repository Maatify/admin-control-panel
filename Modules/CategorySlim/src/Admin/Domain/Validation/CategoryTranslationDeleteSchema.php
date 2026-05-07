<?php

declare(strict_types=1);

namespace Maatify\CategorySlim\Admin\Domain\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Rules\Primitive\EntityIdRule;
use Maatify\Validation\Schemas\AbstractSchema;

final class CategoryTranslationDeleteSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'language_id' => [
                EntityIdRule::required(),
                ValidationErrorCodeEnum::REQUIRED_FIELD,
            ],
        ];
    }
}

