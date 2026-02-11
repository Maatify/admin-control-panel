<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Language\Validation;

use Maatify\I18n\Enum\TextDirectionEnum;
use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

final class LanguageCreateSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'name' => [
                v::stringType()->length(1, 100),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],

            'code' => [
                v::stringType()->length(2, 10),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],

            'direction' => [
                v::in(array_map(
                    static fn(TextDirectionEnum $e): string => $e->value,
                    TextDirectionEnum::cases()
                )),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],

            'icon' => [
                v::optional(v::stringType()->length(1, 255)),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],

            'is_active' => [
                v::optional(v::boolVal()),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],

            'fallback_language_id' => [
                v::optional(v::intVal()->min(1)),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
        ];
    }
}
