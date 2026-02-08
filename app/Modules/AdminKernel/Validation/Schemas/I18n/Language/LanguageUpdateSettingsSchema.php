<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Validation\Schemas\I18n\Language;

use Maatify\I18n\Enum\TextDirectionEnum;
use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

final class LanguageUpdateSettingsSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'language_id' => [
                v::intVal()->min(1),
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
        ];
    }
}
