<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Validation\Schemas\I18n\TranslationKey;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

final class TranslationKeyUpdateNameSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'key_id' => [
                v::intVal()->min(1),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],

            'key_name' => [
                v::stringType()->length(1, 255),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
        ];
    }
}
