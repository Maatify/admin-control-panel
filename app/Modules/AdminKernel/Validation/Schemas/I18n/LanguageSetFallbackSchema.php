<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Validation\Schemas\I18n;

use Maatify\Validation\Schemas\AbstractSchema;
use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Respect\Validation\Validator as v;

final class LanguageSetFallbackSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'language_id' => [
                v::intVal()->min(1),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],

            'fallback_language_id' => [
                v::intVal()->min(1),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
        ];
    }
}
