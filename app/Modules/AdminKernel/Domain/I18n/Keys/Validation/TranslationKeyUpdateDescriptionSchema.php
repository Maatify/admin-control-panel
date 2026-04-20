<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Keys\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Rules\PositiveEntityIdRule;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

final class TranslationKeyUpdateDescriptionSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'key_id' => [
                PositiveEntityIdRule::rule(),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],

            'description' => [
                v::stringType()->length(0, 255),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
        ];
    }
}
