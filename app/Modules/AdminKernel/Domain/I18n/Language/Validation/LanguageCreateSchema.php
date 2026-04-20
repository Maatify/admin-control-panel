<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Language\Validation;

use Maatify\LanguageCore\Enum\TextDirectionEnum;
use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Rules\Primitive\BooleanRule;
use Maatify\Validation\Rules\Primitive\EntityIdRule;
use Maatify\Validation\Rules\Primitive\StringRule;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

final class LanguageCreateSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'name' => [
                StringRule::required(min: 1, max: 100),
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
                StringRule::optional(min: 1, max: 255),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],

            'is_active' => [
                BooleanRule::optional(),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],

            'fallback_language_id' => [
                EntityIdRule::optional(),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
        ];
    }
}
