<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Scope\Validation;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Rules\Semantic\I18nCodeRule;
use Maatify\Validation\Rules\Primitive\EntityIdRule;
use Maatify\Validation\Schemas\AbstractSchema;

final class I18nScopeChangeCodeSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'id' => [
                EntityIdRule::required(),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],

            'new_code' => [
                I18nCodeRule::rule(1, 50),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
        ];
    }
}
