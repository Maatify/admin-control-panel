<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Keys\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Rules\Semantic\I18nCodeRule;
use Maatify\Validation\Rules\Primitive\EntityIdRule;
use Maatify\Validation\Schemas\AbstractSchema;

final class TranslationKeyUpdateNameSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'key_id' => [
                EntityIdRule::required(),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],

            'key_name' => [
                I18nCodeRule::rule(1, 128),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
        ];
    }
}
