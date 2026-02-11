<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Keys\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

final class TranslationKeyUpdateDescriptionSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'key_id' => [
                v::intVal()->min(1),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],

            'description' => [
                v::stringType()->length(0, 255),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
        ];
    }
}
