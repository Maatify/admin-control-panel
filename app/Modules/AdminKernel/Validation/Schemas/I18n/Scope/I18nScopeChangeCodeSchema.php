<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Validation\Schemas\I18n\Scope;

use Maatify\Validation\Enum\ValidationErrorCodeEnum;
use Maatify\Validation\Schemas\AbstractSchema;
use Respect\Validation\Validator as v;

final class I18nScopeChangeCodeSchema extends AbstractSchema
{
    protected function rules(): array
    {
        return [
            'id' => [
                v::intVal()->min(1),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],

            'new_code' => [
                v::stringType()->length(1, 50),
                ValidationErrorCodeEnum::REQUIRED_FIELD
            ],
        ];
    }
}
