<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Keys\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Rules\I18nCodeRule;
use Maatify\Validation\Rules\PositiveEntityIdRule;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

final class TranslationKeyUpdateNameSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'key_id' => [
                PositiveEntityIdRule::rule(),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],

            'key_name' => [
                I18nCodeRule::rule(1, 128),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
        ];
    }
}
