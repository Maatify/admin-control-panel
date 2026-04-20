<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Keys\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Rules\Primitive\EntityIdRule;
use Maatify\Validation\Rules\Primitive\StringRule;
use Maatify\Validation\Schemas\AbstractSchema;

final class TranslationKeyUpdateDescriptionSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'key_id' => [
                EntityIdRule::required(),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],

            'description' => [
                StringRule::required(min: 0, max: 255),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
        ];
    }
}
