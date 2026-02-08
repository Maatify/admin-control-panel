<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Validation\Schemas\I18n\TranslationKey;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

final class TranslationKeyCreateSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'key_name' => [
                v::stringType()->length(1, 255),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],

            'description' => [
                v::optional(v::stringType()->length(0, 2000)),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
        ];
    }
}
